<!-- Modal de Información sobre Días Proporcionales -->
<div class="modal fade" id="infoDiasModal" tabindex="-1" role="dialog" aria-labelledby="infoDiasModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
    <div class="modal-content shadow-sm border-0 rounded-lg">

      <!-- Header -->
      <div class="modal-header bg-white border-bottom d-flex align-items-center">
        <h5 class="modal-title text-dark mb-0" id="infoDiasModalLabel">
          Cálculo de tus Días Acumulados
        </h5>

        <button type="button"
                class="btn btn-light btn-sm rounded-circle shadow-sm"
                data-dismiss="modal"
                aria-label="Cerrar"
                style="
                  position: absolute;
                  top: 16px;
                  right: 16px;
                  width: 32px;
                  height: 32px;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  z-index: 10;
                ">
          <span aria-hidden="true" class="text-dark" style="font-size: 1.2rem;">&times;</span>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
        <div class="alert alert-light border mb-3">
          <p class="mb-2 text-muted">
            Tus días proporcionales se calculan con la fórmula:
          </p>
          <p class="font-weight-bold text-dark">
            Meses trabajados × 1,25 − días de vacaciones tomados
          </p>
        </div>

        <div class="alert alert-info border-0">
          <p class="mb-1">
            <strong>Ejemplo:</strong> Si entraste el <u>01/09/2023</u>, al día de hoy acumularías
            aprox. <strong>30 días</strong>.  
            Si has usado hasta ahora <strong>23 días</strong>, tu saldo es de <strong>6,8 días</strong>.
          </p>
        </div>

        {{-- <small class="text-muted d-block mt-3">
          * El sistema considera meses incompletos y redondea a decimales.
          El saldo oficial es siempre el mostrado en tu perfil.
        </small> --}}
      </div>

      <!-- Footer -->
      <div class="modal-footer bg-light border-top d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
          Cerrar
        </button>
      </div>

    </div>
  </div>
</div>
