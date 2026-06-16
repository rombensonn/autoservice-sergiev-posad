const form = document.querySelector('.lead-form');

if (form) {
    const status = form.querySelector('.form-status');
    const submit = form.querySelector('button[type="submit"]');
    const originalText = submit ? submit.innerHTML : '';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        status.textContent = '';
        status.className = 'form-status';

        if (!form.reportValidity()) {
            return;
        }

        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Отправляем...';
        }

        if (form.action.includes('#booking')) {
            status.textContent = 'Заявка подготовлена. Для записи позвоните по телефону выше.';
            status.classList.add('is-success');
            form.reset();

            if (submit) {
                submit.disabled = false;
                submit.innerHTML = originalText;
            }

            return;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            status.textContent = data.message || (data.ok ? 'Заявка отправлена.' : 'Не удалось отправить заявку.');
            status.classList.add(data.ok ? 'is-success' : 'is-error');

            if (data.ok) {
                form.reset();
            }
        } catch (error) {
            status.textContent = 'Не удалось отправить заявку. Позвоните по телефону выше.';
            status.classList.add('is-error');
        } finally {
            if (submit) {
                submit.disabled = false;
                submit.innerHTML = originalText;
            }
        }
    });
}

const desktopFinePointer = window.matchMedia('(hover: hover) and (pointer: fine) and (min-width: 769px)');

function bindGlassPointer() {
    if (!desktopFinePointer.matches) {
        return;
    }

    const surfaces = document.querySelectorAll([
        '.booking-panel',
        '.proof-grid div',
        '.trust-points li',
        '.service-card',
        '.steps li',
        '.review-card',
        'details',
        '.contact-card',
        '.map-wrap',
    ].join(','));

    surfaces.forEach((surface) => {
        surface.addEventListener('pointermove', (event) => {
            const rect = surface.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            surface.style.setProperty('--mx', `${x.toFixed(2)}%`);
            surface.style.setProperty('--my', `${y.toFixed(2)}%`);
        });

        surface.addEventListener('pointerleave', () => {
            surface.style.removeProperty('--mx');
            surface.style.removeProperty('--my');
        });
    });
}

bindGlassPointer();
