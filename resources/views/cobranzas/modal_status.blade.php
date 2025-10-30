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
                            <option value="Pronto pago" {{ $doc->status == 'Pronto pago' ? 'selected' : '' }}>Pronto pago</option>
                            <option value="Cobranza judicial" {{ $doc->status == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                        </select>
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
                <form action="{{ route('documentos.cruces.store', $doc->id) }}" 
                    method="POST" 
                    id="form-cruce-{{ $doc->id }}" 
                    style="display: {{ $doc->status == 'Cruce' ? 'block' : 'none' }};">
                    @csrf

                    {{-- Saldo pendiente --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text" 
                            class="form-control form-control-sm"
                            value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                            readonly>
                    </div>

                    {{-- Monto del cruce --}}
                    <div class="form-group mb-3">
                        <label for="monto-cruce-{{ $doc->id }}" class="form-label small text-muted">Monto del cruce</label>
                        <input type="number" 
                            name="monto" 
                            id="monto-cruce-{{ $doc->id }}" 
                            class="form-control form-control-sm @error('monto') is-invalid @enderror" 
                            min="1" 
                            required>
                        @error('monto')
                            <span class="invalid-feedback d-block text-danger"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Fecha del cruce --}}
                    <div class="form-group mb-3">
                        <label for="fecha-cruce-{{ $doc->id }}" class="form-label small text-muted">Fecha del cruce</label>
                        <input type="date" 
                            name="fecha_cruce" 
                            id="fecha-cruce-{{ $doc->id }}" 
                            class="form-control form-control-sm @error('fecha_cruce') is-invalid @enderror" 
                            required>
                        @error('fecha_cruce')
                            <span class="invalid-feedback d-block text-danger"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Seleccionar proveedor --}}
                    {{-- Seleccionar proveedor --}}
                    <div class="form-group mb-3">
                        <label for="proveedor_id-{{ $doc->id }}" class="form-label small text-muted">
                            Seleccionar proveedor
                        </label>

                        <select name="proveedor_id" 
                                id="proveedor_id-{{ $doc->id }}" 
                                class="form-select form-select-sm @error('proveedor_id') is-invalid @enderror" 
                                required>
                            <option value="">-- Seleccionar proveedor --</option>
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">
                                    {{ $proveedor->razon_social }} — RUT: {{ $proveedor->rut }}
                                </option>
                            @endforeach
                        </select>

                        @error('proveedor_id')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                        {{-- 🔹 Enlace rápido para crear nuevo proveedor --}}
                        <div class="mt-1">
                            <a href="{{ route('proveedores.create') }}" target="_blank" class="small text-primary text-decoration-none">
                                <i class="bi bi-plus-circle"></i> Agregar nuevo proveedor
                            </a>
                        </div>
                    </div>

                </form>



                {{-- FORMULARIO DE PAGO --}}
                <form action="{{ route('documentos.pagos.store', $doc->id) }}" method="POST" id="form-pago-{{ $doc->id }}" style="display: {{ $doc->status == 'Pago' ? 'block' : 'none' }};">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="fecha-pago-{{ $doc->id }}" class="form-label small text-muted">Fecha del pago</label>
                        <input type="date"
                            name="fecha_pago"
                            id="fecha-pago-{{ $doc->id }}"
                            class="form-control form-control-sm @error('fecha_pago') is-invalid @enderror"
                            value="{{ now()->format('Y-m-d') }}"
                            required>
                        @error('fecha_pago')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- ✅ Campo oculto para mantener coherencia con "Fecha Estado Manual" --}}
                    <input type="hidden" name="fecha_estado_manual" value="{{ old('fecha_estado_manual', now()->format('Y-m-d')) }}">

                    <div class="alert alert-info py-1 px-2 small">
                        Al registrar un pago, el saldo pendiente quedará automáticamente en <strong>0</strong>.
                    </div>
                </form>


                {{-- FORMULARIO DE PRONTO PAGO (nuevo) --}}
                <form action="{{ route('prontopagos.store', $doc->id) }}" method="POST" id="form-prontopago-{{ $doc->id }}" style="display: {{ $doc->status == 'Pronto pago' ? 'block' : 'none' }};">
                    @csrf

                    {{-- Fecha del pronto pago --}}
                    <div class="form-group mb-3">
                        <label for="fecha-prontopago-{{ $doc->id }}" class="form-label small text-muted">Fecha del pronto pago</label>
                        <input type="date"
                            name="fecha_pronto_pago"
                            id="fecha-prontopago-{{ $doc->id }}"
                            class="form-control form-control-sm @error('fecha_pronto_pago') is-invalid @enderror"
                            value="{{ now()->format('Y-m-d') }}"
                            required>
                        @error('fecha_pronto_pago')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
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
        const formPago = document.getElementById('form-pago-' + id);
        const formProntoPago = document.getElementById('form-prontopago-' + id);
        const formEstado = document.getElementById('form-status-' + id);

        // Ocultar todos
        formAbono.style.display = 'none';
        formCruce.style.display = 'none';
        formPago.style.display = 'none';
        formProntoPago.style.display = 'none';

        // Mostrar el que corresponda
        if (estado === 'Abono') {
            formAbono.style.display = 'block';
        } else if (estado === 'Cruce') {
            formCruce.style.display = 'block';
        } else if (estado === 'Pago') {
            formPago.style.display = 'block';
        } else if (estado === 'Pronto pago') {
            formProntoPago.style.display = 'block';
        }

        // Mostrar u ocultar campo de fecha manual
        formEstado.querySelector('.fecha-estado-' + id).style.display =
            ['Abono', 'Cruce', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(estado)
                ? 'block'
                : 'none';
    }

    function submitModalForm(id) {
        const estado = document.getElementById('status-' + id).value;
        if (estado === 'Abono') {
            document.getElementById('form-abono-' + id).submit();
        } else if (estado === 'Cruce') {
            document.getElementById('form-cruce-' + id).submit();
        } else if (estado === 'Pago') {
            document.getElementById('form-pago-' + id).submit();
        } else if (estado === 'Pronto pago') {
            document.getElementById('form-prontopago-' + id).submit();
        } else {
            document.getElementById('form-status-' + id).submit();
        }
    }
</script>



