<div class="modal fade" id="modalPagosMasivos" tabindex="-1" aria-labelledby="modalPagosMasivosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
                {{-- 🔍 Búsqueda --}}
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

                {{-- 📋 Resultados de búsqueda --}}
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
                                    <th>Saldo Pendiente</th>
                                </tr>
                            </thead>
                            <tbody id="resultados-body">
                                {{-- Aquí se insertarán las filas vía JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- 📅 Fecha de pago --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha de pago</label>
                        <input type="date" name="fecha_pago" class="form-control form-control-sm" required>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados
                        </button>
                    </div>
                </form>

                {{-- 🕳️ Mensaje cuando no hay resultados --}}
                <div id="sin-resultados" class="text-center text-muted mt-3" style="display:none;">
                    <i class="bi bi-inbox"></i> No se encontraron documentos.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- === SCRIPT === --}}
<script>
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
                        <td><input type="checkbox" name="documentos[]" value="${doc.id}"></td>
                        <td>${doc.folio ?? ''}</td>
                        <td>${doc.razon_social ?? ''}</td>
                        <td>${doc.rut_proveedor ?? ''}</td>
                        <td>$${Number(doc.monto_total || 0).toLocaleString('es-CL')}</td>
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
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>
