{{-- resources/views/pagos/modal_detalle.blade.php --}}

<!-- Modal Detalle Próximos Pagos -->
<div class="modal fade" id="modalProximosPagos" tabindex="-1" role="dialog" aria-labelledby="modalProximosPagosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalProximosPagosLabel">Detalle de Pagos</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <table class="table table-sm table-hover">
          <thead class="thead-light">
            <tr>
              <th>Proveedor</th>
              <th>Empresa</th>
              <th>Fecha Vencimiento</th>
              <th class="text-right">Monto</th>
            </tr>
          </thead>
          <tbody id="detalle-pagos-body">
            {{-- Aquí se insertarán las filas dinámicamente con JS --}}
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

{{-- Script para poblar el modal --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalProximosPagos');
    const tbody = document.getElementById('detalle-pagos-body');

    // Escuchar cuando se abre el modal a través de los botones .ver-detalle-pagos
    document.querySelectorAll('.ver-detalle-pagos').forEach(btn => {
        btn.addEventListener('click', function () {
            // Limpiar el contenido anterior
            tbody.innerHTML = '';

            // Parsear el JSON con los detalles
            const detalles = JSON.parse(this.dataset.detalle);

            if (detalles.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Sin detalles disponibles</td></tr>`;
                return;
            }

            // Construir las filas
            detalles.forEach(item => {
                const fila = `
                    <tr>
                        <td>${item.proveedor}</td>
                        <td>${item.empresa}</td>
                        <td>${item.fecha_vencimiento}</td>
                        <td class="text-right">$${parseInt(item.monto).toLocaleString('es-CL')}</td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', fila);
            });
        });
    });
});
</script>
