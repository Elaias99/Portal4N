<div class="modal fade" id="modalEditDocumento-{{ $doc->id }}" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel-{{ $doc->id }}" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">

      {{-- === HEADER === --}}
      <div class="modal-header position-relative">
        <h5 class="modal-title fw-bold" id="modalEditLabel-{{ $doc->id }}">
          Editar Documento Financiero
        </h5>

        {{-- Botón de cierre flotante, igual que en modal_status --}}
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

      {{-- === BODY === --}}
      <div class="modal-body">
        <form id="formEditDocumento-{{ $doc->id }}" 
              method="POST" 
              action="{{ route('cobranzas.documentos.update', $doc->id) }}">
          @csrf
          @method('PUT')

          <div class="row g-3">
            {{-- RAZÓN SOCIAL --}}
            <div class="col-md-6">
              <label for="razon_social-{{ $doc->id }}" class="form-label small text-muted">Razón Social</label>
              <input type="text" name="razon_social" id="razon_social-{{ $doc->id }}"
                     class="form-control form-control-sm"
                     value="{{ old('razon_social', $doc->razon_social) }}" required>
            </div>

            {{-- FOLIO --}}
            <div class="col-md-3">
              <label for="folio-{{ $doc->id }}" class="form-label small text-muted">Folio</label>
              <input type="text" name="folio" id="folio-{{ $doc->id }}"
                     class="form-control form-control-sm"
                     value="{{ old('folio', $doc->folio) }}">
            </div>

            {{-- MONTO TOTAL --}}
            <div class="col-md-3">
              <label for="monto_total-{{ $doc->id }}" class="form-label small text-muted">Monto Total</label>
              <input type="number" name="monto_total" id="monto_total-{{ $doc->id }}"
                     class="form-control form-control-sm"
                     value="{{ old('monto_total', $doc->monto_total) }}" min="0" required>
            </div>

            {{-- FECHA DOCUMENTO --}}
            <div class="col-md-6">
              <label for="fecha_docto-{{ $doc->id }}" class="form-label small text-muted">Fecha Documento</label>
              <input type="date" name="fecha_docto" id="fecha_docto-{{ $doc->id }}"
                     class="form-control form-control-sm"
                     value="{{ old('fecha_docto', $doc->fecha_docto ? \Carbon\Carbon::parse($doc->fecha_docto)->format('Y-m-d') : '') }}"
                     required>
            </div>

            {{-- FECHA VENCIMIENTO --}}
            <div class="col-md-6">
              <label for="fecha_vencimiento-{{ $doc->id }}" class="form-label small text-muted">Fecha Vencimiento</label>
              <input type="date" name="fecha_vencimiento" id="fecha_vencimiento-{{ $doc->id }}"
                     class="form-control form-control-sm"
                     value="{{ old('fecha_vencimiento', $doc->fecha_vencimiento ? \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('Y-m-d') : '') }}">
            </div>
          </div>
        </form>
      </div>

      {{-- === FOOTER === --}}
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm" form="formEditDocumento-{{ $doc->id }}">
          <i class="bi bi-save"></i> Guardar cambios
        </button>
      </div>

    </div>
  </div>
</div>
