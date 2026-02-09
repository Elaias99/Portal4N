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
                                    <th>Operación</th>
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

                <!-- ✅ NUEVO: selector de operación por documento -->
                <td style="width:160px;">
                    <select class="form-select form-select-sm op-doc"
                        data-id="${doc.id}"
                        onchange="onOperacionChange(this)"
                        disabled>
                        <option value="pago" selected>Pago total</option>
                        <option value="abono">Abono</option>
                    </select>
                </td>

                <td style="width:160px;">
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

    const inputMonto = document.querySelector(`.monto-abono[data-id="${id}"]`);
    const selectOp  = document.querySelector(`.op-doc[data-id="${id}"]`);

    if (checkbox.checked) {



        const saldoInicial = Number(checkbox.dataset.saldo || 0);
        const operacionActual = selectOp?.value || 'pago';
        const montoActual =
            operacionActual === 'abono'
                ? Number(inputMonto?.value || 0)
                : saldoInicial;

        documentosSeleccionados[id] = {
            id,
            folio: checkbox.dataset.folio,
            razon: checkbox.dataset.razon,
            saldoInicial,
            operacion: operacionActual,
            monto: montoActual
        };





        // habilitar select operación
        if (selectOp) {
            selectOp.disabled = false;
            upsertHiddenOperacion(id, selectOp.value);
        }

        // habilitar monto solo si es abono
        if (inputMonto) {
            inputMonto.disabled = selectOp?.value !== 'abono';

            if (selectOp?.value === 'abono') {
                upsertHiddenMonto(id, inputMonto.value);
            }
        }


    } else {
        // ❌ quitar selección
        delete documentosSeleccionados[id];

        // eliminar hidden inputs
        document
            .querySelector(`#contenedor-montos-hidden input[name="montos[${id}]"]`)
            ?.remove();

        document
            .querySelector(`#contenedor-montos-hidden input[name="operaciones[${id}]"]`)
            ?.remove();

        // deshabilitar inputs visibles
        if (inputMonto) inputMonto.disabled = true;
        if (selectOp)  selectOp.disabled  = true;
    }


    renderSeleccionados();
}






function onOperacionChange(select) {
    const id = select.dataset.id;
    const op = select.value; // "pago" | "abono"

    const checkbox = document.querySelector(
        `#resultados-body input[type="checkbox"][data-id="${id}"]`
    );
    const inputMonto = document.querySelector(
        `.monto-abono[data-id="${id}"]`
    );

    // Guardar operación en hidden
    upsertHiddenOperacion(id, op);

    // Si el documento no está seleccionado, no hacemos nada más
    if (!checkbox?.checked || !documentosSeleccionados[id]) {
        return;
    }

    documentosSeleccionados[id].operacion = op;

    if (op === 'pago') {
        const saldo = documentosSeleccionados[id].saldoInicial;

        // Estado interno
        documentosSeleccionados[id].monto = saldo;

        // Input visible
        if (inputMonto) {
            inputMonto.value = saldo;
            inputMonto.disabled = true;
        }

        // Hidden
        upsertHiddenMonto(id, saldo);

    } else { // abono
        const monto = Number(inputMonto?.value || 0);

        documentosSeleccionados[id].monto = monto;

        if (inputMonto) {
            inputMonto.disabled = false;
        }

        upsertHiddenMonto(id, monto);
    }

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

        const operacionTexto =
            doc.operacion === 'abono' ? 'Abono' : 'Pago';

        const monto = doc.monto;
        const saldoFinal =
            doc.operacion === 'pago'
                ? 0
                : Math.max(doc.saldoInicial - monto, 0);

        const li = document.createElement('li');
        li.className =
            'list-group-item d-flex justify-content-between align-items-start small';

        li.innerHTML = `
            <div>
                <div class="fw-semibold">
                    ${doc.folio} — ${doc.razon}
                </div>
                <div class="text-muted">
                    ${operacionTexto}
                    | $${monto.toLocaleString('es-CL')}
                    | Saldo: $${doc.saldoInicial.toLocaleString('es-CL')}
                    → $${saldoFinal.toLocaleString('es-CL')}
                </div>
            </div>

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


function upsertHiddenOperacion(id, valor) {
    const contenedor = document.getElementById('contenedor-montos-hidden');

    let input = contenedor.querySelector(`input[name="operaciones[${id}]"]`);

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = `operaciones[${id}]`;
        contenedor.appendChild(input);
    }

    input.value = valor;
}



document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('monto-abono')) return;

    const id = e.target.dataset.id; // 👈 ESTA LÍNEA FALTABA
    const valor = Number(e.target.value || 0);

    upsertHiddenMonto(id, valor);

    if (documentosSeleccionados[id]) {
        documentosSeleccionados[id].monto = valor;
    }

    renderSeleccionados();
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

        btn.disabled = true;
        btn.innerHTML = 'Procesando pagos...';

        const formData = new FormData(form);

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

            // ✅ NUEVO: descargar un archivo por empresa
            if (Array.isArray(data.downloads)) {
                data.downloads.forEach((item, index) => {
                    setTimeout(() => {
                        window.location.href = item.url;
                    }, index * 800); // pequeño delay para evitar colisiones
                });
            }

            // Feedback visual
            if (!document.getElementById('msg-pagos-ok')) {
                const msg = document.createElement('div');
                msg.id = 'msg-pagos-ok';
                msg.className = 'alert alert-success mt-3';
                msg.innerText =
                    'Pagos procesados correctamente. Se generaron los archivos por empresa.';
                form.prepend(msg);
            }

            btn.disabled = false;
            btn.innerHTML = 'Registrar Pagos Seleccionados';
        });



    });

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modalEl = document.getElementById('modalPagosMasivos');

    if (!modalEl) return;

    modalEl.addEventListener('hidden.bs.modal', function () {
        // 🔁 Refrescar solo cuando el usuario cierra el modal
        window.location.reload();
    });

});
</script>



