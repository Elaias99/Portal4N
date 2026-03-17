    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-exportar');
        if (!form) return;

        const btn = document.getElementById('btn-exportar');
        const texto = document.getElementById('texto-exportar');
        const spinner = document.getElementById('spinner-exportar');

        form.addEventListener('submit', function () {
            btn.disabled = true;
            texto.textContent = 'Generando archivo...';
            spinner.classList.remove('d-none');

            setTimeout(function () {
                btn.disabled = false;
                texto.textContent = 'Exportar';
                spinner.classList.add('d-none');
            }, 4000);
        });
    });




    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modalServicio');
        if (!modal) return;

        const form = document.getElementById('formServicioUpdate');
        const title = document.getElementById('modalServicioTitle');
        const input = document.getElementById('inputServicioManual');

        modal.addEventListener('show.bs.modal', (event) => {
            const btn = event.relatedTarget;
            if (!btn) return;

            const updateUrl = btn.getAttribute('data-update-url') || '';
            const folio = btn.getAttribute('data-folio') || '';
            const servicio = btn.getAttribute('data-servicio') || '';

            form.setAttribute('action', updateUrl);
            title.textContent = folio ? `Definir servicio – Folio ${folio}` : 'Definir servicio';
            input.value = servicio;
            setTimeout(() => input.focus(), 150);
        });
    });









    document.addEventListener('DOMContentLoaded', () => {
        const tipoSelect = document.querySelector('select[name="servicio_tipo"]');
        const selectProveedor = document.getElementById('servicioProveedorSelect');
        const inputManual = document.getElementById('servicioManualInput');

        if (!tipoSelect || !selectProveedor || !inputManual) return;

        function toggleServicioInput() {
            const tipo = tipoSelect.value;

            selectProveedor.classList.add('d-none');
            inputManual.classList.add('d-none');

            selectProveedor.disabled = true;
            inputManual.disabled = true;

            if (tipo === 'proveedor') {
                selectProveedor.classList.remove('d-none');
                selectProveedor.disabled = false;
            }

            if (tipo === 'manual') {
                inputManual.classList.remove('d-none');
                inputManual.disabled = false;
            }
        }

        toggleServicioInput();
        tipoSelect.addEventListener('change', toggleServicioInput);
    });




    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dropdown.keep-open-on-drag').forEach(function (dd) {
            let startedInside = false;

            dd.addEventListener('mousedown', function (e) {
                if (e.target.closest('.dropdown-menu')) startedInside = true;
            });

            const menu = dd.querySelector('.dropdown-menu');
            if (menu) {
                menu.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }

            document.addEventListener('click', function (e) {
                if (!startedInside) return;
                startedInside = false;

                if (!e.target.closest('.dropdown.keep-open-on-drag')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);
        });
    });