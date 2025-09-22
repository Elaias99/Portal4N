{{-- resources/views/cotizadores/modal_transporte.blade.php --}}

<div class="modal fade" id="modalTransporte" tabindex="-1" aria-labelledby="modalTransporteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      {{-- Header --}}
      <div class="modal-header">
        <h5 class="modal-title" id="modalTransporteLabel">Detalle de Transporte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      {{-- Body --}}
      <div class="modal-body">

        <div class="mb-3">
          <label for="transporte_id" class="form-label">Tipo de movilización</label>
          <select name="transporte_id" id="transporte_id" class="form-select" >
            <option value="">-- Selecciona un tipo --</option>
            @foreach($transportes as $transporte)
              <option value="{{ $transporte->id }}" data-perfil="{{ $transporte->perfil_api }}">
                {{ $transporte->nombre }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label for="Origen" class="form-label">Origen</label>
          <input type="text" name="Origen" id="Origen" class="form-control" >
        </div>

        <div class="mb-3">
          <label for="Destino" class="form-label">Destino</label>
          <input type="text" name="Destino" id="Destino" class="form-control" >
        </div>

        {{-- Coordenadas ocultas --}}
        <input type="hidden" name="origen_lat" id="origen_lat">
        <input type="hidden" name="origen_lon" id="origen_lon">
        <input type="hidden" name="destino_lat" id="destino_lat">
        <input type="hidden" name="destino_lon" id="destino_lon">

        <div class="mb-3">
          <label for="distancia_km" class="form-label">Distancia (km)</label>
          <input type="number" step="0.01" name="distancia_km" id="distancia_km" class="form-control" >
        </div>

        <div class="mb-3">
          <button type="button" id="btnCalcular" class="btn btn-secondary">Calcular distancia</button>
          <span id="resultadoDistancia" class="ms-3 text-primary fw-bold"></span>
        </div>

        <div id="resultadoRuta" class="mt-3"></div>

      </div>

      {{-- Footer --}}
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Guardar</button>
      </div>

    </div>
  </div>
</div>
