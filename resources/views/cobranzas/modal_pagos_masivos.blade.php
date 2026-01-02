<div class="modal fade" id="modalPagosMasivos" tabindex="-1" aria-labelledby="modalPagosMasivosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            {{-- === HEADER === --}}
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalPagosMasivosLabel">
                    Pagos Masivos — Selección de Documentos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- === BODY === --}}
            <div class="modal-body">
                {{-- Búsqueda --}}
                <form id="form-busqueda-pagos" method="GET" onsubmit="buscarDocumentosMasivos(event)">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" id="filtro" name="filtro" class="form-control form-control-sm"
                                placeholder="Buscar por folio o razón social..." required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>







                {{-- Resultados de búsqueda --}}
                <form id="form-pagos-masivos" action="{{ route('documentos.pagos.masivo') }}" method="POST">

                    {{-- Tipo de operación --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tipo de operación</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                    type="radio"
                                    name="tipo_operacion"
                                    id="tipoPago"
                                    value="pago"
                                    checked
                                    onchange="toggleTipoOperacion()">
                                <label class="form-check-label small" for="tipoPago">
                                    Pago total
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input"
                                    type="radio"
                                    name="tipo_operacion"
                                    id="tipoAbono"
                                    value="abono"
                                    onchange="toggleTipoOperacion()">
                                <label class="form-check-label small" for="tipoAbono">
                                    Abono
                                </label>
                            </div>
                        </div>
                    </div>


                    @csrf

                    <div id="tabla-resultados" class="table-responsive mb-3" style="display:none;">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll" onclick="toggleSelectAll(this)"></th>
                                    <th>Folio</th>
                                    <th>Razón Social</th>
                                    <th>RUT</th>
                                    <th>Monto Total</th>
                                    <th>Monto a pagar</th>
                                    <th>Saldo Pendiente</th>
                                </tr>
                            </thead>
                            <tbody id="resultados-body">
                                {{-- Aquí se insertarán las filas vía JS --}}
                            </tbody>
                        </table>
                    </div>



                    {{-- Documentos seleccionados --}}
                    <div id="contenedor-seleccionados" class="card mb-3" style="display:none;">
                        <div class="card-header fw-bold small">
                            Documentos seleccionados para pago
                        </div>
                        <div class="card-body p-2">
                            <ul id="lista-seleccionados" class="list-group list-group-flush"></ul>
                        </div>
                    </div>







                    {{-- Fecha de pago --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha de pago</label>
                        <input type="date" name="fecha_pago" class="form-control form-control-sm" required>
                    </div>

                    <div class="text-end">


                        <button type="submit" id="btn-registrar-pagos" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados
                        </button>


                    </div>

                    <div id="contenedor-montos-hidden"></div>
                </form>

                


                {{-- Mensaje cuando no hay resultados --}}
                <div id="sin-resultados" class="text-center text-muted mt-3" style="display:none;">
                    <i class="bi bi-inbox"></i> No se encontraron documentos.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- === SCRIPT === --}}
<script>


let documentosSeleccionados = {};

/**
 * Buscar documentos pendientes para pagos masivos.
 * Se ejecuta al enviar el formulario dentro del modal.
 */
function buscarDocumentosMasivos(e) {
    e.preventDefault();

    const filtro = document.getElementById('filtro').value.trim();
    const tbody = document.getElementById('resultados-body');
    const tabla = document.getElementById('tabla-resultados');
    const vacio = document.getElementById('sin-resultados');
    const spinner = document.getElementById('spinner-busqueda');

    // 🔹 Limpiar resultados anteriores
    tbody.innerHTML = '';
    tabla.style.display = 'none';
    vacio.style.display = 'none';

    if (!filtro) return;

    // 🔹 Mostrar indicador de carga
    if (spinner) spinner.style.display = 'block';

    fetch(`/api/documentos/buscar?filtro=${encodeURIComponent(filtro)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Ocultar spinner
            if (spinner) spinner.style.display = 'none';

            if (!Array.isArray(data) || data.length === 0) {
                vacio.style.display = 'block';
                return;
            }

            tabla.style.display = 'table';
            vacio.style.display = 'none';

            data.forEach(doc => {



            const fila = `
            <tr>
                <td>
                    <input type="checkbox"
                        onchange="toggleDocumento(this)"
                        data-id="${doc.id}"
                        data-folio="${doc.folio}"
                        data-razon="${doc.razon_social}"
                        data-saldo="${doc.saldo_pendiente}">
                </td>

                <td>${doc.folio ?? ''}</td>
                <td>${doc.razon_social ?? ''}</td>
                <td>${doc.rut_proveedor ?? ''}</td>

                <td>$${Number(doc.monto_total || 0).toLocaleString('es-CL')}</td>

                <td>
                    <input type="number"
                        class="form-control form-control-sm monto-abono"
                        min="1"
                        max="${doc.saldo_pendiente}"
                        step="1"
                        data-id="${doc.id}"
                        value="${doc.saldo_pendiente}"
                        disabled>
                </td>

                <td>$${Number(doc.saldo_pendiente || 0).toLocaleString('es-CL')}</td>
            </tr>
            `;







                tbody.insertAdjacentHTML('beforeend', fila);
            });
        })
        .catch(error => {
            console.error('❌ Error en la búsqueda masiva:', error);
            alert('Ocurrió un error al buscar documentos. Revisa la consola para más detalles.');
            if (spinner) spinner.style.display = 'none';
        });
}

/**
 * Seleccionar o deseleccionar todos los checkboxes de la tabla.
 */
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('#resultados-body input[type="checkbox"]');

    checkboxes.forEach(cb => {
        cb.checked = source.checked;
        toggleDocumento(cb);
    });
}



function toggleDocumento(checkbox) {
    const id = checkbox.dataset.id;

    if (checkbox.checked) {
        documentosSeleccionados[id] = {
            id,
            folio: checkbox.dataset.folio,
            razon: checkbox.dataset.razon
        };

        const inputVisible = document.querySelector(`.monto-abono[data-id="${id}"]`);
        if (inputVisible) {
            upsertHiddenMonto(id, inputVisible.value);
        }

    } else {
        delete documentosSeleccionados[id];

        document
            .querySelector(`#contenedor-montos-hidden input[name="montos[${id}]"]`)
            ?.remove();
    }


    // 🔑 habilitar/deshabilitar monto según selección + tipo
    const inputMonto = document.querySelector(`.monto-abono[data-id="${id}"]`);
    const esAbono = document.getElementById('tipoAbono').checked;

    if (inputMonto) {
        inputMonto.disabled = !(checkbox.checked && esAbono);
    }

    console.log('documentosSeleccionados:', JSON.parse(JSON.stringify(documentosSeleccionados)));

    renderSeleccionados();
}



function renderSeleccionados() {
    const contenedor = document.getElementById('contenedor-seleccionados');
    const lista = document.getElementById('lista-seleccionados');

    lista.innerHTML = '';

    const ids = Object.keys(documentosSeleccionados);

    if (ids.length === 0) {
        contenedor.style.display = 'none';
        return;
    }

    contenedor.style.display = 'block';

    ids.forEach(id => {
        const doc = documentosSeleccionados[id];

        // habilitar input monto solo si está seleccionado
        const inputMonto = document.querySelector(`.monto-abono[data-id="${id}"]`);
        if (inputMonto) {
            inputMonto.disabled = false;
        }

        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center small';

        li.innerHTML = `
            <span>
                <strong>${doc.folio}</strong> — ${doc.razon}
            </span>
            <button type="button"
                class="btn btn-sm btn-outline-danger"
                onclick="quitarDocumento(${id})">
                ✕
            </button>

            <input type="hidden" name="documentos[]" value="${id}">
        `;

        lista.appendChild(li);
    });
}


function quitarDocumento(id) {
    delete documentosSeleccionados[id];

    document.querySelectorAll(
        `input[type="checkbox"][data-id="${id}"]`
    ).forEach(cb => cb.checked = false);

    const inputMonto = document.querySelector(`.monto-abono[data-id="${id}"]`);
    if (inputMonto) {
        inputMonto.disabled = true;
    }

    renderSeleccionados();
}


function toggleTipoOperacion() {
    const esAbono = document.getElementById('tipoAbono').checked;

    document.querySelectorAll('.monto-abono').forEach(input => {
        const id = input.dataset.id;
        const seleccionado = !!documentosSeleccionados[id];

        input.disabled = !(esAbono && seleccionado);

        if (!esAbono) {
            input.value = input.max;
        }
    });
}


function upsertHiddenMonto(id, valor) {
    const contenedor = document.getElementById('contenedor-montos-hidden');

    let input = contenedor.querySelector(`input[name="montos[${id}]"]`);

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = `montos[${id}]`;
        contenedor.appendChild(input);
    }

    input.value = valor;
}



document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('monto-abono')) return;

    const id = e.target.dataset.id;
    upsertHiddenMonto(id, e.target.value);
});









</script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('form-pagos-masivos');
    const btn  = document.getElementById('btn-registrar-pagos');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // detener submit normal

        renderSeleccionados();

        console.log('🧾 Inputs montos en DOM:', 
            Array.from(document.querySelectorAll('input[name^="montos["]'))
                .map(i => ({
                    name: i.name,
                    value: i.value,
                    disabled: i.disabled
                }))
        );


        btn.disabled = true;
        btn.innerHTML = 'Procesando pagos...';

        const formData = new FormData(form);

        console.log('📦 FormData enviado:');
        for (let pair of formData.entries()) {
            console.log(pair[0], pair[1]);
        }

        // 1️⃣ Registrar pagos (POST)
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Error registrando pagos');
            return res.json();
        })
        .then(data => {
            if (!data.ok) {
                throw new Error('Respuesta inválida');
            }

            //Descargar Excel (GET)
            window.location.href = "{{ route('documentos.pagos.masivo.export') }}";

            // Cerrar modal y refrescar después
            setTimeout(() => {


                const modalEl = document.getElementById('modalPagosMasivos');

                if (modalEl) {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.hide();
                }






                window.location.reload();
            }, 2500);
        })
        .catch(err => {
            console.error(err);
            alert('Ocurrió un error al registrar los pagos');
            btn.disabled = false;
            btn.innerHTML = 'Registrar Pagos Seleccionados';
        });
    });

});
</script>


