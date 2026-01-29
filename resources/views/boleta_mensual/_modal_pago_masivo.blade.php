<div class="modal fade"
     id="modalPagoMasivo"
     tabindex="-1"
     aria-labelledby="modalPagoMasivoLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalPagoMasivoLabel">
                    Pago masivo de honorarios
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <form method="POST"
                  action="{{ route('honorarios.mensual.pago.masivo') }}">
                @csrf

                <div class="modal-body">

                    {{-- =========================
                         BUSCADOR
                    ========================== --}}
                    <div class="mb-3">
                        <label class="form-label">Buscar honorarios</label>
                        <input type="text"
                               id="buscador-honorarios"
                               class="form-control"
                               placeholder="Buscar por folio, RUT o emisor">
                    </div>

                    {{-- =========================
                         RESULTADOS DE BÚSQUEDA
                    ========================== --}}
                    <div class="mb-3">
                        <label class="form-label">Resultados</label>

                        <div id="resultados-honorarios"
                             class="border rounded p-2"
                             style="max-height: 200px; overflow-y: auto;">
                            {{-- resultados --}}
                        </div>
                    </div>

                    <hr>

                    {{-- =========================
                         SELECCIONADOS
                    ========================== --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Honorarios que quedarán como pagados
                        </label>

                        <div id="honorarios-seleccionados"
                             class="border rounded p-2"
                             style="min-height: 100px;">
                        </div>
                    </div>

                    {{-- =========================
                         INPUTS OCULTOS
                    ========================== --}}
                    <div id="inputs-honorarios-seleccionados"></div>

                    <hr>

                    {{-- =========================
                         FECHA DE PAGO (AL FINAL)
                    ========================== --}}
                    <div class="mb-3">
                        <label class="form-label">Fecha de pago</label>
                        <input type="date"
                               name="fecha_pago"
                               class="form-control"
                               required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Confirmar pago masivo
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>



<script>
/**
 * =========================
 * CACHE GLOBAL DE RESULTADOS
 * =========================
 */
const resultadosCache = {};

/**
 * =========================
 * BUSCADOR
 * =========================
 */
document.addEventListener('DOMContentLoaded', function () {

    const buscador   = document.getElementById('buscador-honorarios');
    const resultados = document.getElementById('resultados-honorarios');

    if (!buscador || !resultados) return;

    let controller = null;

    buscador.addEventListener('keyup', function () {

        const q = this.value.trim();

        if (q.length < 2) {
            resultados.innerHTML = '';
            return;
        }

        if (controller) controller.abort();
        controller = new AbortController();

        fetch(`{{ route('honorarios.mensual.buscar') }}?q=${encodeURIComponent(q)}`, {
            signal: controller.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {

            if (!Array.isArray(data) || data.length === 0) {
                resultados.innerHTML =
                    `<div class="text-muted small">No se encontraron resultados</div>`;
                return;
            }

            let html = '';

            data.forEach(h => {

                // no mostrar si ya está seleccionado
                if (document.getElementById('sel-' + h.id)) return;

                html += `
                    <div class="p-2 border-bottom resultado-honorario"
                         style="cursor:pointer"
                         data-id="${h.id}"
                         data-folio="${h.folio}"
                         data-rut="${h.rut_emisor}"
                         data-emisor="${h.razon_social_emisor}"
                         data-saldo="${h.saldo_pendiente}">
                        <div><strong>Folio:</strong> ${h.folio}</div>
                        <div><strong>RUT:</strong> ${h.rut_emisor}</div>
                        <div><strong>Emisor:</strong> ${h.razon_social_emisor}</div>
                        <div><strong>Saldo pendiente:</strong> ${h.saldo_pendiente}</div>
                        <div class="text-muted small">Click para seleccionar</div>
                    </div>
                `;
            });

            resultados.innerHTML = html;
        })
        .catch(() => {
            resultados.innerHTML =
                `<div class="text-danger small">Error al buscar honorarios</div>`;
        });
    });
});


/**
 * =========================
 * SELECCIONAR HONORARIO
 * =========================
 */
document.addEventListener('click', function (e) {

    const item = e.target.closest('.resultado-honorario');
    if (!item) return;

    const id = item.dataset.id;

    // evitar duplicados
    if (document.getElementById('sel-' + id)) return;

    // 🔹 guardar en cache ANTES de quitar
    resultadosCache[id] = item.outerHTML;

    const contenedor = document.getElementById('honorarios-seleccionados');
    const inputs     = document.getElementById('inputs-honorarios-seleccionados');

    const div = document.createElement('div');
    div.id = 'sel-' + id;
    div.className = 'border rounded p-2 mb-2 bg-light d-flex justify-content-between gap-2';

    div.innerHTML = `
        <div>
            <div><strong>Folio:</strong> ${item.dataset.folio}</div>
            <div><strong>RUT:</strong> ${item.dataset.rut}</div>
            <div><strong>Emisor:</strong> ${item.dataset.emisor}</div>
            <div><strong>Saldo:</strong> ${item.dataset.saldo}</div>
        </div>

        <button type="button"
                class="btn btn-outline-danger btn-sm btn-quitar-seleccionado"
                data-id="${id}">
            Quitar
        </button>
    `;

    contenedor.appendChild(div);

    const input = document.createElement('input');
    input.type  = 'hidden';
    input.name  = 'honorarios[]';
    input.value = id;
    input.id    = 'input-sel-' + id;

    inputs.appendChild(input);

    // quitar de resultados
    item.remove();
});


/**
 * =========================
 * QUITAR SELECCIONADO
 * =========================
 */
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.btn-quitar-seleccionado');
    if (!btn) return;

    const id = btn.dataset.id;

    // eliminar bloque visual
    const bloque = document.getElementById('sel-' + id);
    if (bloque) bloque.remove();

    // eliminar input hidden
    const input = document.getElementById('input-sel-' + id);
    if (input) input.remove();

    // 🔹 volver a mostrar en RESULTADOS
    if (resultadosCache[id]) {
        const resultados = document.getElementById('resultados-honorarios');
        resultados.insertAdjacentHTML('afterbegin', resultadosCache[id]);
        delete resultadosCache[id];
    }
});
</script>


