<div class="modal fade" id="modalEstadoCompra-{{ $doc->id }}" tabindex="-1" role="dialog" aria-labelledby="modalEstadoLabel-{{ $doc->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            {{-- === HEADER === --}}
            <div class="modal-header position-relative">
                <h5 class="modal-title fw-bold" id="modalEstadoLabel-{{ $doc->id }}">
                    Actualizar estado — {{ $doc->razon_social }} — Folio {{ $doc->folio }}
                </h5>

                <button type="button"
                        class="btn btn-light btn-sm rounded-circle shadow-sm"
                        data-dismiss="modal"
                        aria-label="Cerrar"
                        style="position:absolute;top:16px;right:16px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                    <span aria-hidden="true" class="text-dark" style="font-size:1.2rem;">&times;</span>
                </button>
            </div>

            {{-- === BODY === --}}
            <div class="modal-body">

                {{-- === FORMULARIO PRINCIPAL === --}}
                <form action="{{ route('finanzas_compras.updateEstado', $doc->id) }}"
                      method="POST"
                      id="form-estado-{{ $doc->id }}">
                    @csrf
                    @method('PATCH')

                    {{-- Estado actual --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Estado actual</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $doc->estado_visible }}"
                               readonly>
                    </div>

                    {{-- Nuevo estado --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Nuevo estado</label>
                        <select name="estado"
                                id="estado-{{ $doc->id }}"
                                class="form-select form-select-sm"
                                onchange="toggleEstadoForm({{ $doc->id }})">
                            <option value="">Sin estado manual</option>
                            <option value="Abono" {{ $doc->estado == 'Abono' ? 'selected' : '' }}>Abono</option>
                            <option value="Cruce" {{ $doc->estado == 'Cruce' ? 'selected' : '' }}>Cruce</option>
                            <option value="Pago" {{ $doc->estado == 'Pago' ? 'selected' : '' }}>Pagado</option>
                            <option value="Pronto pago" {{ $doc->estado == 'Pronto pago' ? 'selected' : '' }}>Pronto pago</option>
                            <option value="Cobranza judicial" {{ $doc->estado == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                        </select>
                    </div>
                </form>

                {{-- === FORMULARIO DE ABONO === --}}
                <form action="{{ route('finanzas_compras.abonos.store', $doc->id) }}"
                      method="POST"
                      id="form-abono-{{ $doc->id }}"
                      style="display:{{ $doc->estado == 'Abono' ? 'block' : 'none' }};">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Monto del abono</label>
                        <input type="number"
                               name="monto"
                               class="form-control form-control-sm"
                               min="1"
                               required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del abono</label>
                        <input type="date"
                               name="fecha_abono"
                               class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>
                </form>

                {{-- === FORMULARIO DE CRUCE === --}}
                <form action="{{ route('finanzas_compras.cruces.store', $doc->id) }}"
                      method="POST"
                      id="form-cruce-{{ $doc->id }}"
                      style="display:{{ $doc->estado == 'Cruce' ? 'block' : 'none' }};">
                    @csrf

                    {{-- Saldo pendiente --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Saldo pendiente
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    {{-- Monto del cruce --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Monto del cruce
                        </label>

                        <input type="number"
                               name="monto"
                               class="form-control form-control-sm @error('monto') is-invalid @enderror"
                               min="1"
                               required>

                        @error('monto')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Fecha del cruce --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Fecha del cruce
                        </label>

                        <input type="date"
                               name="fecha_cruce"
                               class="form-control form-control-sm @error('fecha_cruce') is-invalid @enderror"
                               value="{{ now()->format('Y-m-d') }}"
                               required>

                        @error('fecha_cruce')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Proveedor asociado del documento --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Proveedor asociado
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $doc->cobranzaCompra?->razon_social ?? $doc->razon_social }} — RUT: {{ $doc->cobranzaCompra?->rut_cliente ?? $doc->rut_proveedor }}"
                               readonly>

                        <small class="text-muted">
                            El cruce se asociará automáticamente al proveedor del documento.
                        </small>

                        @error('cobranza_compra_id')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Cliente asociado en Cuentas por Cobrar --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Cliente asociado en CxC
                        </label>

                        @if($doc->cobranzaClienteAsociada)
                            <input type="text"
                                   class="form-control form-control-sm"
                                   value="{{ $doc->cobranzaClienteAsociada->razon_social }} — RUT: {{ $doc->cobranzaClienteAsociada->rut_cliente }}"
                                   readonly>

                            <small class="text-success">
                                Este proveedor también existe como cliente en Cuentas por Cobrar.
                            </small>
                        @else
                            <input type="text"
                                   class="form-control form-control-sm text-muted"
                                   value="No existe cliente asociado en Cuentas por Cobrar para este RUT"
                                   readonly>

                            <small class="text-warning">
                                No se encontró una cobranza cliente con el mismo RUT del proveedor.
                            </small>
                        @endif
                    </div>

                    {{-- Documentos asociados en Cuentas por Cobrar --}}
                    @if($doc->cobranzaClienteAsociada)
                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">
                                Documentos asociados en CxC
                            </label>

                            @php
                                $documentosFinancierosAsociados = $doc->documentosFinancierosAsociados
                                    ->where('saldo_pendiente', '>', 0)
                                    ->whereNotIn('tipo_documento_id', [61, 56])
                                    ->sortByDesc('fecha_vencimiento');
                            @endphp

                            @if($documentosFinancierosAsociados->isNotEmpty())
                                <div class="border rounded p-2 bg-light"
                                     style="max-height: 220px; overflow-y: auto;">

                                    @foreach($documentosFinancierosAsociados as $docFinanciero)
                                        <div class="small py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input js-doc-cruce-check"
                                                       id="doc-financiero-cruce-{{ $doc->id }}-{{ $docFinanciero->id }}"
                                                       name="documentos_financieros_cruce[]"
                                                       value="{{ $docFinanciero->id }}"
                                                       data-form-id="{{ $doc->id }}"
                                                       data-saldo="{{ (int) $docFinanciero->saldo_pendiente }}">

                                                <label class="form-check-label w-100"
                                                       for="doc-financiero-cruce-{{ $doc->id }}-{{ $docFinanciero->id }}">
                                                    <div class="fw-semibold">
                                                        Folio {{ $docFinanciero->folio }}
                                                        — {{ $docFinanciero->tipoDocumento?->nombre ?? 'Documento financiero' }}
                                                    </div>

                                                    <div class="text-muted">
                                                        Empresa:
                                                        {{ $docFinanciero->empresa?->Nombre ?? 'Sin empresa' }}
                                                    </div>

                                                    <div>
                                                        Saldo pendiente:
                                                        <span class="fw-semibold text-danger">
                                                            ${{ number_format($docFinanciero->saldo_pendiente, 0, ',', '.') }}
                                                        </span>
                                                    </div>

                                                    <div class="text-muted">
                                                        Fecha documento:
                                                        {{ $docFinanciero->fecha_docto ? \Carbon\Carbon::parse($docFinanciero->fecha_docto)->format('d-m-Y') : '-' }}
                                                        —
                                                        Vencimiento:
                                                        {{ $docFinanciero->fecha_vencimiento ? \Carbon\Carbon::parse($docFinanciero->fecha_vencimiento)->format('d-m-Y') : '-' }}
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-2 p-2 border rounded bg-white">
                                    <span class="small text-muted">
                                        Total seleccionado:
                                    </span>

                                    <span class="fw-bold text-primary js-total-cruce-seleccionado"
                                          data-form-id="{{ $doc->id }}">
                                        $0
                                    </span>
                                </div>

                                <small class="text-muted">
                                    Estos documentos pertenecen al cliente asociado en Cuentas por Cobrar.
                                </small>
                            @else
                                <input type="text"
                                       class="form-control form-control-sm text-muted"
                                       value="No hay documentos pendientes en CxC para este cliente"
                                       readonly>

                                <small class="text-warning">
                                    El cliente existe, pero no tiene documentos pendientes disponibles para cruce.
                                </small>
                            @endif
                        </div>
                    @endif
                </form>

                {{-- === FORMULARIO DE PAGO === --}}
                <form action="{{ route('documentos.pagos.store', $doc->id) }}"
                      method="POST"
                      id="form-pago-{{ $doc->id }}"
                      style="display:{{ $doc->estado == 'Pago' ? 'block' : 'none' }};">
                    @csrf

                    <input type="hidden" name="tipo" value="compra">

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del pago</label>
                        <input type="date"
                               name="fecha_pago"
                               class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>

                    <div class="alert alert-info py-1 px-2 small">
                        Al registrar un pago, el saldo pendiente quedará automáticamente en <strong>0</strong>.
                    </div>
                </form>

                {{-- === FORMULARIO DE PRONTO PAGO === --}}
                <form action="{{ route('prontopagos.store', $doc->id) }}"
                      method="POST"
                      id="form-prontopago-{{ $doc->id }}"
                      style="display:{{ $doc->estado == 'Pronto pago' ? 'block' : 'none' }};">
                    @csrf

                    <input type="hidden" name="tipo" value="compra">

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Fecha del pronto pago</label>
                        <input type="date"
                               name="fecha_pronto_pago"
                               class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>
                </form>

            </div>

            {{-- === FOOTER === --}}
            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary btn-sm"
                        data-dismiss="modal">
                    Cancelar
                </button>

                <button type="button"
                        class="btn btn-primary btn-sm"
                        id="btn-guardar-estado-{{ $doc->id }}"
                        onclick="submitEstadoForm({{ $doc->id }})">
                    <i class="bi bi-save"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- === SCRIPT === --}}
<script>
function toggleEstadoForm(id) {
    const estadoSelect = document.getElementById('estado-' + id);

    if (!estadoSelect) {
        return;
    }

    const estado = estadoSelect.value;
    const forms = ['abono', 'cruce', 'pago', 'prontopago'];

    forms.forEach(f => {
        const form = document.getElementById('form-' + f + '-' + id);

        if (form) {
            form.style.display = 'none';
        }
    });

    if (estado === 'Abono') {
        document.getElementById('form-abono-' + id)?.style.setProperty('display', 'block');
    } else if (estado === 'Cruce') {
        document.getElementById('form-cruce-' + id)?.style.setProperty('display', 'block');
    } else if (estado === 'Pago') {
        document.getElementById('form-pago-' + id)?.style.setProperty('display', 'block');
    } else if (estado === 'Pronto pago') {
        document.getElementById('form-prontopago-' + id)?.style.setProperty('display', 'block');
    }
}

function getEstadoForm(id) {
    const estado = document.getElementById('estado-' + id)?.value;

    if (estado === 'Abono') {
        return document.getElementById('form-abono-' + id);
    }

    if (estado === 'Cruce') {
        return document.getElementById('form-cruce-' + id);
    }

    if (estado === 'Pago') {
        return document.getElementById('form-pago-' + id);
    }

    if (estado === 'Pronto pago') {
        return document.getElementById('form-prontopago-' + id);
    }

    return document.getElementById('form-estado-' + id);
}

function submitEstadoForm(id) {
    const form = getEstadoForm(id);
    const btnGuardar = document.getElementById('btn-guardar-estado-' + id);

    if (!form) {
        return;
    }

    /*
     * form.submit() no dispara validación HTML5 automáticamente.
     * Por eso validamos manualmente antes de mostrar el spinner.
     */
    if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
        return;
    }

    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
    }

    window.pageLoader?.show({ timeout: 30000 });

    try {
        form.submit();
    } catch (error) {
        window.pageLoader?.forceHide?.();

        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-save"></i> Guardar cambios';
        }

        console.error('Error al enviar formulario de estado:', error);
    }
}

document.addEventListener('change', function (event) {
    const checkbox = event.target;

    if (!checkbox.classList.contains('js-doc-cruce-check')) {
        return;
    }

    const formId = checkbox.dataset.formId;

    const checks = document.querySelectorAll(
        '.js-doc-cruce-check[data-form-id="' + formId + '"]'
    );

    let total = 0;

    checks.forEach(function (item) {
        if (item.checked) {
            total += Number(item.dataset.saldo || 0);
        }
    });

    const totalElement = document.querySelector(
        '.js-total-cruce-seleccionado[data-form-id="' + formId + '"]'
    );

    if (totalElement) {
        totalElement.textContent = total.toLocaleString('es-CL', {
            style: 'currency',
            currency: 'CLP',
            maximumFractionDigits: 0
        });
    }
});
</script>