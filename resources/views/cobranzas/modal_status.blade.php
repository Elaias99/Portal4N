<div class="modal fade" id="modalStatus-{{ $doc->id }}" tabindex="-1" role="dialog" aria-labelledby="modalStatusLabel-{{ $doc->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            {{-- Encabezado --}}
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatusLabel-{{ $doc->id }}">
                    Actualizar estado - {{ $doc->razon_social }}
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

            <div class="modal-body">

                {{-- === Formulario para actualizar estado === --}}
                <form action="{{ route('documentos.updateStatus', $doc->id) }}" method="POST" id="form-status-{{ $doc->id }}">
                    @csrf
                    @method('PATCH')

                    {{-- Estado --}}
                    <div class="form-group">
                        <label for="status-{{ $doc->id }}">Estado</label>
                        <select name="status" id="status-{{ $doc->id }}" class="form-control" onchange="toggleAbonoFields({{ $doc->id }})">
                            <option value="">Sin estado</option>
                            <option value="Abono" {{ $doc->status == 'Abono' ? 'selected' : '' }}>Abono</option>
                            <option value="Cobranza judicial" {{ $doc->status == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                            <option value="Pago" {{ $doc->status == 'Pago' ? 'selected' : '' }}>Pago</option>
                        </select>
                    </div>

                    {{-- Fecha Estado Manual --}}
                    <div class="form-group">
                        <label for="fecha_estado_manual-{{ $doc->id }}">Fecha Estado Manual</label>
                        <input type="date" 
                            name="fecha_estado_manual" 
                            id="fecha_estado_manual-{{ $doc->id }}" 
                            class="form-control"
                            value="{{ $doc->fecha_estado_manual 
                                        ? \Carbon\Carbon::parse($doc->fecha_estado_manual)->format('Y-m-d') 
                                        : now()->format('Y-m-d') }}">
                    </div>
                </form>

                {{-- === Formulario exclusivo para abonos === --}}
                <form action="{{ route('documentos.abonos.store', $doc->id) }}" method="POST" id="form-abono-{{ $doc->id }}" style="display: none;">
                    @csrf

                    {{-- Mostrar saldo pendiente --}}
                    <div class="form-group">
                        <label>Saldo Pendiente del Documento</label>
                        <input type="text" class="form-control" 
                            value="${{ number_format($doc->monto_total - $doc->abonos->sum('monto'), 0, ',', '.') }}" 
                            readonly>
                    </div>


                    {{-- Registrar abono --}}
                    <div class="form-group">
                        <label for="monto-abono-{{ $doc->id }}">Monto Abono</label>
                        <input type="number" 
                            name="monto" 
                            id="monto-abono-{{ $doc->id }}" 
                            class="form-control @error('monto') is-invalid @enderror" 
                            min="1" 
                            required>

                        {{-- Aquí se muestra el error si existe --}}
                        @error('monto')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong class="text-danger">{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>



                    <div class="form-group">
                        <label for="fecha-abono-{{ $doc->id }}">Fecha Abono</label>
                        <input type="date" 
                            name="fecha_abono" 
                            id="fecha-abono-{{ $doc->id }}" 
                            class="form-control @error('fecha_abono') is-invalid @enderror" 
                            required>

                        {{-- Mostrar error de validación si existe --}}
                        @error('fecha_abono')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong class="text-danger">{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                </form>

            </div>

            {{-- Botones --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>

                {{-- Botón dinámico: decide qué form enviar --}}
                <button type="button" class="btn btn-primary" onclick="submitModalForm({{ $doc->id }})">Guardar</button>
            </div>

        </div>
    </div>
</div>

<script>
    function toggleAbonoFields(id) {
        const statusSelect = document.getElementById('status-' + id);
        const abonoForm = document.getElementById('form-abono-' + id);
        const statusForm = document.getElementById('form-status-' + id);

        if (statusSelect.value === 'Abono') {
            abonoForm.style.display = 'block';
            statusForm.style.display = 'none'; // 👈 ocultar el otro
        } else {
            abonoForm.style.display = 'none';
            statusForm.style.display = 'block'; // 👈 mostrar el otro
        }
    }


    function submitModalForm(id) {
        const statusSelect = document.getElementById('status-' + id);
        if (statusSelect.value === 'Abono') {
            document.getElementById('form-abono-' + id).submit();
        } else {
            document.getElementById('form-status-' + id).submit();
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        @foreach($documentoFinancieros as $doc)
            toggleAbonoFields({{ $doc->id }});
        @endforeach
    });
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    @if ($errors->has('monto') || $errors->has('fecha_abono'))
        let errorInput = document.querySelector('input.is-invalid');
        if (errorInput) {
            let modal = errorInput.closest('.modal');
            if (modal) {
                $(modal).modal('show');
            }
        }
    @endif

});
</script>

