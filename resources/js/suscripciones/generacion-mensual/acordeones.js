/**
 * resources/js/suscripciones/generacion-mensual/acordeones.js
 *
 * Maneja únicamente la apertura/cierre de paneles colapsables
 * usados en la pantalla de preparación mensual.
 */

export function inicializarAcordeonesSuscripciones() {
    document.querySelectorAll('[data-suscripcion-toggle]').forEach(function (button) {
        if (button.dataset.dropdownInicializado === '1') {
            return;
        }

        button.dataset.dropdownInicializado = '1';

        button.addEventListener('click', function () {
            const targetSelector = button.getAttribute('data-suscripcion-toggle');
            const target = document.querySelector(targetSelector);

            if (!target) {
                return;
            }

            const estaCerrado = target.classList.contains('d-none');

            target.classList.toggle('d-none', !estaCerrado);
            button.setAttribute('aria-expanded', estaCerrado ? 'true' : 'false');

            const icon = button.querySelector('[data-suscripcion-toggle-icon]');

            if (icon) {
                icon.textContent = estaCerrado ? '⌃' : '⌄';
            }
        });
    });
}