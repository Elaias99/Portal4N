{{-- resources/views/cotizadores/modal_insumos.blade.php --}}

<div class="modal fade" id="modalInsumos" tabindex="-1" aria-labelledby="modalInsumosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      {{-- Header --}}
      <div class="modal-header">
        <h5 class="modal-title" id="modalInsumosLabel">Detalle de Insumos (Proveedor)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      {{-- Body con tabla dinámica --}}
      <div class="modal-body">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Detalle</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tablaInsumosBody">
            <tr>
              <td><input type="text" name="insumos[0][detalle]" class="form-control" required></td>
              <td><input type="number" name="insumos[0][cantidad]" class="form-control cantidad" min="1" required></td>
              <td><input type="number" step="0.01" name="insumos[0][precio]" class="form-control precio" min="0" required></td>
              <td><input type="text" class="form-control subtotal" readonly></td>
              <td><button type="button" class="btn btn-sm btn-danger btnEliminar">✖</button></td>
            </tr>
          </tbody>
        </table>
        <button type="button" id="btnAgregarInsumo" class="btn btn-sm btn-secondary">➕ Agregar insumo</button>

        <div class="mt-3 text-end">
          <strong>Total: </strong> <span id="totalInsumos">0</span>
        </div>
      </div>

      {{-- Footer con botones --}}
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarInsumos" data-bs-dismiss="modal">Guardar</button>
      </div>

    </div>
  </div>
</div>
<script>
  document.addEventListener("DOMContentLoaded", function() {
      const tablaBody = document.getElementById("tablaInsumosBody");
      const btnAgregar = document.getElementById("btnAgregarInsumo");
      const totalSpan = document.getElementById("totalInsumos");

      // Formateador de moneda en CLP
      const formatter = new Intl.NumberFormat("es-CL", {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
      });

      function recalcularTotales() {
          let total = 0;
          tablaBody.querySelectorAll("tr").forEach(tr => {
              const cantidad = parseFloat(tr.querySelector(".cantidad").value) || 0;
              const precio = parseFloat(tr.querySelector(".precio").value) || 0;
              const subtotal = cantidad * precio;

              // Mostrar subtotal formateado
              tr.querySelector(".subtotal").value = formatter.format(subtotal);

              total += subtotal;
          });

          // Mostrar total formateado
          totalSpan.textContent = formatter.format(total);
      }

      if (btnAgregar) {
          btnAgregar.addEventListener("click", () => {
              const index = tablaBody.querySelectorAll("tr").length;
              const row = document.createElement("tr");
              row.innerHTML = `
                  <td><input type="text" name="insumos[${index}][detalle]" class="form-control" required></td>
                  <td><input type="number" name="insumos[${index}][cantidad]" class="form-control cantidad" min="1" required></td>
                  <td><input type="number" step="0.01" name="insumos[${index}][precio]" class="form-control precio" min="0" required></td>
                  <td><input type="text" class="form-control subtotal" readonly></td>
                  <td><button type="button" class="btn btn-sm btn-danger btnEliminar">✖</button></td>
              `;
              tablaBody.appendChild(row);
          });
      }

      if (tablaBody) {
          tablaBody.addEventListener("input", (e) => {
              if (e.target.classList.contains("cantidad") || e.target.classList.contains("precio")) {
                  recalcularTotales();
              }
          });

          tablaBody.addEventListener("click", (e) => {
              if (e.target.classList.contains("btnEliminar")) {
                  e.target.closest("tr").remove();
                  recalcularTotales();
              }
          });
      }

      recalcularTotales();
  });
</script>
