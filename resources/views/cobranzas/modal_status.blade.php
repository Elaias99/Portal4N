<div class="modal fade" id="modalStatus-{{ $doc->id }}" tabindex="-1" role="dialog" aria-labelledby="modalStatusLabel-{{ $doc->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            {{-- === HEADER === --}}
            <div class="modal-header position-relative">
                <h5 class="modal-title fw-bold" id="modalStatusLabel-{{ $doc->id }}">
                    Actualizar estado - {{ $doc->razon_social }} - folio {{ $doc->folio }} 
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

            {{-- === BODY === --}}
            <div class="modal-body">

                {{-- FORMULARIO PRINCIPAL --}}
                <form action="{{ route('documentos.updateStatus', $doc->id) }}" method="POST" id="form-status-{{ $doc->id }}">
                    @csrf
                    @method('PATCH')

                    {{-- Estado actual --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Estado actual</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $doc->status_original }}"
                               readonly>
                    </div>

                    {{-- Nuevo estado manual --}}
                    <div class="form-group mb-3">
                        <label for="status-{{ $doc->id }}" class="form-label small text-muted">Nuevo estado manual</label>
                        <select name="status"
                                id="status-{{ $doc->id }}"
                                class="form-select form-select-sm"
                                onchange="toggleEstadoFields({{ $doc->id }})">
                            <option value="">Sin estado manual</option>
                            <option value="Abono" {{ $doc->status == 'Abono' ? 'selected' : '' }}>Abono</option>
                            <option value="Cruce" {{ $doc->status == 'Cruce' ? 'selected' : '' }}>Cruce</option>
                            <option value="Pago" {{ $doc->status == 'Pago' ? 'selected' : '' }}>Pago</option>
                            <option value="Cobranza judicial" {{ $doc->status == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                        </select>
                    </div>

                    {{-- Fecha Estado Manual --}}
                    <div class="form-group mb-3 fecha-estado-{{ $doc->id }}" style="display: {{ in_array($doc->status, ['Abono','Cruce','Pago','Cobranza judicial']) ? 'block' : 'none' }};">
                        <label for="fecha_estado_manual-{{ $doc->id }}" class="form-label small text-muted">Fecha Estado Manual</label>
                        <input type="date"
                               name="fecha_estado_manual"
                               id="fecha_estado_manual-{{ $doc->id }}"
                               class="form-control form-control-sm"
                               value="{{ $doc->fecha_estado_manual ? \Carbon\Carbon::parse($doc->fecha_estado_manual)->format('Y-m-d') : now()->format('Y-m-d') }}">
                    </div>
                </form>

                {{-- FORMULARIO DE ABONO --}}
                <form action="{{ route('documentos.abonos.store', $doc->id) }}" method="POST" id="form-abono-{{ $doc->id }}" style="display: {{ $doc->status == 'Abono' ? 'block' : 'none' }};">
                    @csrf

                    {{-- Saldo pendiente --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text" class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    {{-- Monto del abono --}}
                    <div class="form-group mb-3">
                        <label for="monto-abono-{{ $doc->id }}" class="form-label small text-muted">Monto del abono</label>
                        <input type="number" name="monto" id="monto-abono-{{ $doc->id }}" class="form-control form-control-sm @error('monto') is-invalid @enderror" min="1" required>
                        @error('monto')
                            <span class="invalid-feedback d-block text-danger"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Fecha del abono --}}
                    <div class="form-group mb-3">
                        <label for="fecha-abono-{{ $doc->id }}" class="form-label small text-muted">Fecha del abono</label>
                        <input type="date" name="fecha_abono" id="fecha-abono-{{ $doc->id }}" class="form-control form-control-sm @error('fecha_abono') is-invalid @enderror" required>
                        @error('fecha_abono')
                            <span class="invalid-feedback d-block text-danger"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </form>

                {{-- FORMULARIO DE CRUCE (nuevo) --}}
                <form action="{{ route('documentos.cruces.store', $doc->id) }}" method="POST" id="form-cruce-{{ $doc->id }}" style="display: {{ $doc->status == 'Cruce' ? 'block' : 'none' }};">
                    @csrf

                    {{-- Saldo pendiente --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text" class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    {{-- Monto del cruce --}}
                    <div class="form-group mb-3">
                        <label for="monto-cruce-{{ $doc->id }}" class="form-label small text-muted">Monto del cruce</label>
                        <input type="number" name="monto" id="monto-cruce-{{ $doc->id }}" class="form-control form-control-sm @error('monto') is-invalid @enderror" min="1" required>
                        @error('monto')
                            <span class="invalid-feedback d-block text-danger"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Fecha del cruce --}}
                    <div class="form-group mb-3">
                        <label for="fecha-cruce-{{ $doc->id }}" class="form-label small text-muted">Fecha del cruce</label>
                        <input type="date" name="fecha_cruce" id="fecha-cruce-{{ $doc->id }}" class="form-control form-control-sm @error('fecha_cruce') is-invalid @enderror" required>
                        @error('fecha_cruce')
                            <span class="invalid-feedback d-block text-danger"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </form>
            </div>

            {{-- === FOOTER === --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="submitModalForm({{ $doc->id }})">
                    <i class="bi bi-save"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- === SCRIPT === --}}
<script>
    function toggleEstadoFields(id) {
        const estado = document.getElementById('status-' + id).value;
        const formAbono = document.getElementById('form-abono-' + id);
        const formCruce = document.getElementById('form-cruce-' + id);
        const formEstado = document.getElementById('form-status-' + id);

        // Ocultar ambos formularios
        formAbono.style.display = 'none';
        formCruce.style.display = 'none';

        // Mostrar el que corresponda
        if (estado === 'Abono') {
            formAbono.style.display = 'block';
            formEstado.querySelector('.fecha-estado-' + id).style.display = 'block';
        } else if (estado === 'Cruce') {
            formCruce.style.display = 'block';
            formEstado.querySelector('.fecha-estado-' + id).style.display = 'block';
        } else {
            formEstado.querySelector('.fecha-estado-' + id).style.display =
                ['Pago', 'Cobranza judicial'].includes(estado) ? 'block' : 'none';
        }
    }

    function submitModalForm(id) {
        const estado = document.getElementById('status-' + id).value;
        if (estado === 'Abono') {
            document.getElementById('form-abono-' + id).submit();
        } else if (estado === 'Cruce') {
            document.getElementById('form-cruce-' + id).submit();
        } else {
            document.getElementById('form-status-' + id).submit();
        }
    }
</script>
