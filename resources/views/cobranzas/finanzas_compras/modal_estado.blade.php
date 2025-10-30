<div class="modal fade" id="modalEstadoCompra-{{ $doc->id }}" tabindex="-1" role="dialog" aria-labelledby="modalEstadoLabel-{{ $doc->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            {{-- === HEADER === --}}
            <div class="modal-header position-relative">
                <h5 class="modal-title fw-bold" id="modalEstadoLabel-{{ $doc->id }}">
                    Actualizar estado — {{ $doc->razon_social }} — Folio {{ $doc->folio }}
                </h5>
                <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm"
                        data-dismiss="modal" aria-label="Cerrar"
                        style="position:absolute;top:16px;right:16px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                    <span aria-hidden="true" class="text-dark" style="font-size:1.2rem;">&times;</span>
                </button>
            </div>

            {{-- === BODY === --}}
            <div class="modal-body">

                {{-- === FORMULARIO PRINCIPAL === --}}
                <form action="{{ route('finanzas_compras.updateEstado', $doc->id) }}" method="POST" id="form-estado-{{ $doc->id }}">
                    @csrf
                    @method('PATCH')

                    {{-- Estado actual --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Estado actual</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $doc->status_original }}" readonly>
                    </div>

                    {{-- Nuevo estado --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Nuevo estado</label>
                        <select name="estado" id="estado-{{ $doc->id }}" class="form-select form-select-sm" onchange="toggleEstadoForm({{ $doc->id }})">
                            <option value="">Sin estado manual</option>
                            <option value="Abono" {{ $doc->estado == 'Abono' ? 'selected' : '' }}>Abono</option>
                            <option value="Cruce" {{ $doc->estado == 'Cruce' ? 'selected' : '' }}>Cruce</option>
                            <option value="Pago" {{ $doc->estado == 'Pago' ? 'selected' : '' }}>Pago</option>
                            <option value="Pronto pago" {{ $doc->estado == 'Pronto pago' ? 'selected' : '' }}>Pronto pago</option>
                            <option value="Cobranza judicial" {{ $doc->estado == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                        </select>
                    </div>
                </form>

                {{-- === FORMULARIO DE ABONO === --}}
                <form action="{{ route('finanzas_compras.abonos.store', $doc->id) }}" method="POST" id="form-abono-{{ $doc->id }}" style="display:{{ $doc->estado == 'Abono' ? 'block' : 'none' }};">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text" class="form-control form-control-sm" value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Monto del abono</label>
                        <input type="number" name="monto" class="form-control form-control-sm" min="1" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del abono</label>
                        <input type="date" name="fecha_abono" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </form>

                {{-- === FORMULARIO DE CRUCE === --}}
                <form action="{{ route('finanzas_compras.cruces.store', $doc->id) }}" method="POST" id="form-cruce-{{ $doc->id }}" style="display:{{ $doc->estado == 'Cruce' ? 'block' : 'none' }};">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text" class="form-control form-control-sm" value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Monto del cruce</label>
                        <input type="number" name="monto" class="form-control form-control-sm" min="1" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del cruce</label>
                        <input type="date" name="fecha_cruce" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Proveedor asociado</label>
                        <select name="proveedor_id" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar proveedor --</option>
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">{{ $proveedor->razon_social }} — RUT: {{ $proveedor->rut }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                {{-- === FORMULARIO DE PAGO === --}}
                <form action="{{ route('documentos.pagos.store', $doc->id) }}" method="POST" id="form-pago-{{ $doc->id }}" style="display:{{ $doc->estado == 'Pago' ? 'block' : 'none' }};">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del pago</label>
                        <input type="date" name="fecha_pago" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="alert alert-info py-1 px-2 small">
                        Al registrar un pago, el saldo pendiente quedará automáticamente en <strong>0</strong>.
                    </div>
                </form>

                {{-- === FORMULARIO DE PRONTO PAGO === --}}
                <form action="{{ route('prontopagos.store', $doc->id) }}" method="POST" id="form-prontopago-{{ $doc->id }}" style="display:{{ $doc->estado == 'Pronto pago' ? 'block' : 'none' }};">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del pronto pago</label>
                        <input type="date" name="fecha_pronto_pago" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </form>

            </div>

            {{-- === FOOTER === --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="submitEstadoForm({{ $doc->id }})">
                    <i class="bi bi-save"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- === SCRIPT === --}}
<script>
function toggleEstadoForm(id) {
    const estado = document.getElementById('estado-' + id).value;
    const forms = ['abono', 'cruce', 'pago', 'prontopago'];

    forms.forEach(f => {
        document.getElementById('form-' + f + '-' + id).style.display = 'none';
    });

    if (estado === 'Abono') {
        document.getElementById('form-abono-' + id).style.display = 'block';
    } else if (estado === 'Cruce') {
        document.getElementById('form-cruce-' + id).style.display = 'block';
    } else if (estado === 'Pago') {
        document.getElementById('form-pago-' + id).style.display = 'block';
    } else if (estado === 'Pronto pago') {
        document.getElementById('form-prontopago-' + id).style.display = 'block';
    }
}

function submitEstadoForm(id) {
    const estado = document.getElementById('estado-' + id).value;
    if (estado === 'Abono') {
        document.getElementById('form-abono-' + id).submit();
    } else if (estado === 'Cruce') {
        document.getElementById('form-cruce-' + id).submit();
    } else if (estado === 'Pago') {
        document.getElementById('form-pago-' + id).submit();
    } else if (estado === 'Pronto pago') {
        document.getElementById('form-prontopago-' + id).submit();
    } else {
        document.getElementById('form-estado-' + id).submit();
    }
}
</script>
