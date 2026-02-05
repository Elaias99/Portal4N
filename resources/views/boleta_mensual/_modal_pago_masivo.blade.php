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

            <form method="POST" id="form-pago-masivo"
                  action="{{ route('honorarios.mensual.pago.masivo.exportar') }}">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Buscar honorarios</label>
                        <input type="text"
                               id="buscador-honorarios"
                               class="form-control"
                               placeholder="Buscar por folio, RUT o emisor">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Resultados</label>

                        <div id="resultados-honorarios"
                             class="border rounded p-2"
                             style="max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">
                            Honorarios que quedarán como pagados
                        </label>

                        <div id="honorarios-seleccionados"
                             class="border rounded p-2"
                             style="min-height: 100px;">
                        </div>
                    </div>

                    <div id="inputs-honorarios-seleccionados"></div>

                    <hr>

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
document.addEventListener('DOMContentLoaded', function () {

  // =========================
  // CACHE GLOBAL DE RESULTADOS
  // =========================
  const resultadosCache = {};

  const buscador   = document.getElementById('buscador-honorarios');
  const resultados = document.getElementById('resultados-honorarios');
  const form       = document.getElementById('form-pago-masivo');
  const modalEl    = document.getElementById('modalPagoMasivo');

  if (!buscador || !resultados || !form || !modalEl) return;

  let controller = null;

  // =========================
  // BUSCADOR
  // =========================
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
        resultados.innerHTML = `<div class="text-muted small">No se encontraron resultados</div>`;
        return;
      }

      let html = '';
      data.forEach(h => {
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
      resultados.innerHTML = `<div class="text-danger small">Error al buscar honorarios</div>`;
    });
  });


  // =========================
  // SELECCIONAR / QUITAR
  // =========================
  document.addEventListener('click', function (e) {

    // Seleccionar
    const item = e.target.closest('.resultado-honorario');
    if (item) {
      const id = item.dataset.id;
      if (document.getElementById('sel-' + id)) return;

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

      item.remove();
      return;
    }

    // Quitar seleccionado
    const btn = e.target.closest('.btn-quitar-seleccionado');
    if (btn) {
      const id = btn.dataset.id;

      const bloque = document.getElementById('sel-' + id);
      if (bloque) bloque.remove();

      const input = document.getElementById('input-sel-' + id);
      if (input) input.remove();

      if (resultadosCache[id]) {
        resultados.insertAdjacentHTML('afterbegin', resultadosCache[id]);
        delete resultadosCache[id];
      }
    }
  });


  // =========================
  // SUBMIT: descargar excel + cerrar modal + recargar
  // =========================
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json'
        },
        body: fd,
      });

      if (!res.ok) {
        const ct = res.headers.get('content-type') || '';
        if (ct.includes('application/json')) {
          const data = await res.json();
          const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
          alert(firstError || data.message || 'Error al registrar el pago masivo.');
        } else {
          alert('Error al registrar el pago masivo.');
        }
        return;
      }

      // Descargar
      const blob = await res.blob();

      let filename = 'honorarios_pago_masivo.xlsx';
      const cd = res.headers.get('content-disposition');
      if (cd) {
        const match = cd.match(/filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i);
        if (match) filename = decodeURIComponent(match[1] || match[2]);
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);

      // Cerrar modal (requiere bootstrap.bundle.js)
        $('#modalPagoMasivo').modal('hide');


        // ---- Limpiar selección ----
        document.getElementById('honorarios-seleccionados').innerHTML = '';
        document.getElementById('inputs-honorarios-seleccionados').innerHTML = '';
        document.getElementById('resultados-honorarios').innerHTML = '';
        document.getElementById('buscador-honorarios').value = '';

      // Refrescar vista
      window.location.reload();

    } catch (err) {
      console.error(err);
      alert('Error de red al procesar el pago masivo.');
    }
  });

});
</script>
