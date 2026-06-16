<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод запроса не поддерживается.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function respond(bool $success, string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_text(string $value, int $maxLength): string
{
    $value = trim($value);
    $value = preg_replace('/[\r\n]+/u', ' ', $value) ?? '';
    $value = preg_replace('/\s{2,}/u', ' ', $value) ?? '';
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }
    return substr($value, 0, $maxLength);
}

function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }
    if (preg_match_all('/./us', $value, $matches) !== false) {
        return count($matches[0]);
    }
    return strlen($value);
}

$allowedServices = [
    'Замена масла',
    'Проточка тормозных дисков',
    'Компьютерная диагностика',
    'Ремонт ходовой части',
    'Ремонт тормозной системы',
    'Шиномонтаж и балансировка',
    'Ремонт системы охлаждения',
    'Замена ГРМ и ремонт сцепления',
    'Запчасти и комплектующие под заказ',
];

$honeypot = clean_text((string)($_POST['website'] ?? ''), 100);
if ($honeypot !== '') {
    respond(true, 'Заявка отправлена. Ждите звонка по указанному номеру.');
}

$name = clean_text((string)($_POST['name'] ?? ''), 80);
$phone = clean_text((string)($_POST['phone'] ?? ''), 30);
$service = clean_text((string)($_POST['service'] ?? ''), 120);
$normalizedPhone = preg_replace('/[^\d+]/', '', $phone) ?? '';

if (text_length($name) < 2) {
    respond(false, 'Напишите имя: минимум 2 символа.', 422);
}

if (!preg_match('/^(\+7|7|8)\d{10}$/', $normalizedPhone)) {
    respond(false, 'Введите российский номер телефона.', 422);
}

if (!in_array($service, $allowedServices, true)) {
    respond(false, 'Выберите работу из списка.', 422);
}

$to = (string)(getenv('FORM_TO_EMAIL') ?: 'info@example.com');
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Email получателя настроен неверно.', 500);
}

$hostName = (string)($_SERVER['SERVER_NAME'] ?? '');
$hostName = preg_replace('/:\d+$/', '', $hostName) ?? '';
$hostName = preg_replace('/[^a-z0-9.-]/i', '', $hostName) ?? '';
if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $hostName)) {
    $hostName = 'example.com';
}
$fromEmail = 'no-reply@' . $hostName;

$subject = 'Заявка с лендинга автосервиса';
$site = 'Автосервис, Сергиев Посад, Пограничная ул., 9Б';

$body = implode("\n", [
    'Новая заявка с лендинга',
    '',
    'Сервис: ' . $site,
    'Имя: ' . $name,
    'Телефон: ' . $normalizedPhone,
    'Услуга: ' . $service,
    'Дата: ' . date('d.m.Y H:i:s'),
]);

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . $fromEmail,
    'Reply-To: ' . $fromEmail,
];

$sent = false;

if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $smtpHost = (string)(getenv('SMTP_HOST') ?: '');
        if ($smtpHost !== '') {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->SMTPSecure = (string)(getenv('SMTP_SECURE') ?: 'tls');
            $smtpUser = (string)(getenv('SMTP_USER') ?: '');
            $smtpPassword = (string)(getenv('SMTP_PASSWORD') ?: '');
            if ($smtpUser !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPassword;
            }
        }
        $mail->setFrom($fromEmail, 'Лендинг автосервиса');
        $mail->addReplyTo($fromEmail);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $sent = $mail->send();
    } catch (\Throwable $error) {
        $sent = false;
    }
}

if (!$sent) {
    $sent = mail($to, $subject, $body, implode("\r\n", $headers));
}

if (!$sent) {
    respond(false, 'Заявка не отправилась. Позвоните: +7 (966) 137-32-28.', 500);
}

respond(true, 'Заявка отправлена. Ждите звонка по указанному номеру.');
