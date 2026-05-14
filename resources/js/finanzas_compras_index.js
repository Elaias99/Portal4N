document.addEventListener('DOMContentLoaded', () => {

    // ===============================
    // SELECCIÓN DE DOCUMENTOS
    // ===============================
    const STORAGE_KEY = 'documentosSeleccionados';
    const checkAll = document.getElementById('check-all-documentos');

    function getSeleccion() {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
    }

    function saveSeleccion(data) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function addDocumento(cb) {
        const seleccion = getSeleccion();
        const id = cb.dataset.id;

        seleccion[id] = {
            id: id,
            empresa: cb.dataset.empresa || '',
            folio: cb.dataset.folio,
            razon: cb.dataset.razon,
            rut: cb.dataset.rut || '',
            fechaDocto: cb.dataset.fechaDocto || '',
            fechaVencimiento: cb.dataset.fechaVencimiento || '',
            saldo: Number(cb.dataset.saldo),
            total: Number(cb.dataset.total),

            formaPago: cb.dataset.formaPago || '',
            omitidoBanco: cb.dataset.omitidoBanco === '1'
        };

        saveSeleccion(seleccion);
    }

    function removeDocumento(id) {
        const seleccion = getSeleccion();
        delete seleccion[id];
        saveSeleccion(seleccion);
    }

    const seleccion = getSeleccion();

    document.querySelectorAll('.check-documento').forEach(cb => {
        const id = cb.dataset.id;

        if (seleccion[id]) {
            cb.checked = true;
        }

        cb.addEventListener('change', function () {
            if (this.checked) {
                addDocumento(this);
            } else {
                removeDocumento(this.dataset.id);
            }
        });
    });

    checkAll?.addEventListener('change', function () {
        document.querySelectorAll('.check-documento').forEach(cb => {
            cb.checked = this.checked;

            if (this.checked) {
                addDocumento(cb);
            } else {
                removeDocumento(cb.dataset.id);
            }
        });
    });


    // ===============================
    // MODALES (placeholder bootstrap)
    // ===============================
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            // espacio para lógica futura
        });
    });


    // ===============================
    // DROPDOWN FIX (drag)
    // ===============================
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


// ===============================
// FUNCIÓN GLOBAL (NO tocar)
// ===============================
function toggleFechaEstado(select, id) {
    const inputFecha = document.getElementById('fecha-input-' + id);
    const hiddenFecha = document.getElementById('fecha-hidden-' + id);

    if (['Abono', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(select.value)) {
        if (inputFecha) inputFecha.style.display = 'block';
    } else {
        if (inputFecha) {
            inputFecha.style.display = 'none';
            inputFecha.value = '';
        }
        if (hiddenFecha) hiddenFecha.value = '';
    }
}