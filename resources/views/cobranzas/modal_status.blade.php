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
                               value="{{ $doc->estado_visible }}"
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
                            <option value="Pago" {{ $doc->status == 'Pago' ? 'selected' : '' }}>Pagado</option>
                            <option value="Pronto pago" {{ $doc->status == 'Pronto pago' ? 'selected' : '' }}>Pronto pago</option>
                            <option value="Cobranza judicial" {{ $doc->status == 'Cobranza judicial' ? 'selected' : '' }}>Cobranza judicial</option>
                        </select>
                    </div>
                </form>

                {{-- FORMULARIO DE ABONO --}}
                <form action="{{ route('documentos.abonos.store', $doc->id) }}"
                      method="POST"
                      id="form-abono-{{ $doc->id }}"
                      style="display: {{ $doc->status == 'Abono' ? 'block' : 'none' }};">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label for="monto-abono-{{ $doc->id }}" class="form-label small text-muted">Monto del abono</label>
                        <input type="number"
                               name="monto"
                               id="monto-abono-{{ $doc->id }}"
                               class="form-control form-control-sm @error('monto') is-invalid @enderror"
                               min="1"
                               required>

                        @error('monto')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="fecha-abono-{{ $doc->id }}" class="form-label small text-muted">Fecha del abono</label>
                        <input type="date"
                               name="fecha_abono"
                               id="fecha-abono-{{ $doc->id }}"
                               class="form-control form-control-sm @error('fecha_abono') is-invalid @enderror"
                               required>

                        @error('fecha_abono')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </form>

                {{-- FORMULARIO DE CRUCE --}}
                <form action="{{ route('documentos.cruces.store', $doc->id) }}"
                      method="POST"
                      id="form-cruce-{{ $doc->id }}"
                      style="display: {{ $doc->status == 'Cruce' ? 'block' : 'none' }};">
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
                        <label for="monto-cruce-{{ $doc->id }}" class="form-label small text-muted">
                            Monto del cruce
                        </label>

                        <input type="number"
                               name="monto"
                               id="monto-cruce-{{ $doc->id }}"
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
                        <label for="fecha-cruce-{{ $doc->id }}" class="form-label small text-muted">
                            Fecha del cruce
                        </label>

                        <input type="date"
                               name="fecha_cruce"
                               id="fecha-cruce-{{ $doc->id }}"
                               class="form-control form-control-sm @error('fecha_cruce') is-invalid @enderror"
                               required>

                        @error('fecha_cruce')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Cliente asociado del documento --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Cliente asociado
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $doc->cobranza?->razon_social ?? $doc->razon_social }} — RUT: {{ $doc->cobranza?->rut_cliente ?? $doc->rut_cliente }}"
                               readonly>

                        <small class="text-muted">
                            El cruce se asociará automáticamente al cliente del documento.
                        </small>

                        @error('cobranza_id')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Proveedor asociado en Cuentas por Pagar --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Proveedor asociado en CxP
                        </label>

                        @if($doc->cobranzaCompraAsociada)
                            <input type="text"
                                   class="form-control form-control-sm"
                                   value="{{ $doc->cobranzaCompraAsociada->razon_social }} — RUT: {{ $doc->cobranzaCompraAsociada->rut_cliente }}"
                                   readonly>

                            <small class="text-success">
                                Este cliente también existe como proveedor en Cuentas por Pagar.
                            </small>
                        @else
                            <input type="text"
                                   class="form-control form-control-sm text-muted"
                                   value="No existe proveedor asociado en Cuentas por Pagar para este RUT"
                                   readonly>

                            <small class="text-warning">
                                No se encontró una cobranza de compra con el mismo RUT del cliente.
                            </small>
                        @endif
                    </div>

                    {{-- Documentos asociados en Cuentas por Pagar --}}
                    @if($doc->cobranzaCompraAsociada)
                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">
                                Documentos asociados en CxP
                            </label>

                            @php
                                $documentosCompraAsociados = $doc->documentosCompraAsociados
                                    ->where('saldo_pendiente', '>', 0)
                                    ->whereNotIn('tipo_documento_id', [61, 56])
                                    ->sortByDesc('fecha_vencimiento');
                            @endphp

                            @if($documentosCompraAsociados->isNotEmpty())
                                <div class="border rounded p-2 bg-light"
                                     style="max-height: 220px; overflow-y: auto;">

                                    @foreach($documentosCompraAsociados as $docCompra)
                                        <div class="small py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input js-doc-cruce-check"
                                                       id="doc-compra-cruce-{{ $doc->id }}-{{ $docCompra->id }}"
                                                       name="documentos_compra_cruce[]"
                                                       value="{{ $docCompra->id }}"
                                                       data-form-id="{{ $doc->id }}"
                                                       data-saldo="{{ (int) $docCompra->saldo_pendiente }}">

                                                <label class="form-check-label w-100"
                                                       for="doc-compra-cruce-{{ $doc->id }}-{{ $docCompra->id }}">
                                                    <div class="fw-semibold">
                                                        Folio {{ $docCompra->folio }}
                                                        — {{ $docCompra->tipoDocumento?->nombre ?? 'Documento compra' }}
                                                    </div>

                                                    <div class="text-muted">
                                                        Empresa:
                                                        {{ $docCompra->empresa?->Nombre ?? 'Sin empresa' }}
                                                    </div>

                                                    <div>
                                                        Saldo pendiente:
                                                        <span class="fw-semibold text-danger">
                                                            ${{ number_format($docCompra->saldo_pendiente, 0, ',', '.') }}
                                                        </span>
                                                    </div>

                                                    <div class="text-muted">
                                                        Fecha documento:
                                                        {{ $docCompra->fecha_docto ? \Carbon\Carbon::parse($docCompra->fecha_docto)->format('d-m-Y') : '-' }}
                                                        —
                                                        Vencimiento:
                                                        {{ $docCompra->fecha_vencimiento ? \Carbon\Carbon::parse($docCompra->fecha_vencimiento)->format('d-m-Y') : '-' }}
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
                                    Estos documentos pertenecen al proveedor asociado en Cuentas por Pagar.
                                </small>
                            @else
                                <input type="text"
                                       class="form-control form-control-sm text-muted"
                                       value="No hay documentos pendientes en CxP para este proveedor"
                                       readonly>

                                <small class="text-warning">
                                    El proveedor existe, pero no tiene documentos pendientes disponibles para cruce.
                                </small>
                            @endif
                        </div>
                    @endif
                </form>

                {{-- FORMULARIO DE PAGO --}}
                <form action="{{ route('documentos.pagos.store', $doc->id) }}"
                      method="POST"
                      id="form-pago-{{ $doc->id }}"
                      style="display: {{ $doc->status == 'Pago' ? 'block' : 'none' }};">
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

                    <input type="hidden" name="fecha_estado_manual" value="{{ old('fecha_estado_manual', now()->format('Y-m-d')) }}">

                    <div class="alert alert-info py-1 px-2 small">
                        Al registrar un pago, el saldo pendiente quedará automáticamente en <strong>0</strong>.
                    </div>
                </form>

                {{-- FORMULARIO DE PRONTO PAGO --}}
                <form action="{{ route('prontopagos.store', $doc->id) }}"
                      method="POST"
                      id="form-prontopago-{{ $doc->id }}"
                      style="display: {{ $doc->status == 'Pronto pago' ? 'block' : 'none' }};">
                    @csrf

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

        formAbono.style.display = 'none';
        formCruce.style.display = 'none';
        formPago.style.display = 'none';
        formProntoPago.style.display = 'none';

        if (estado === 'Abono') {
            formAbono.style.display = 'block';
        } else if (estado === 'Cruce') {
            formCruce.style.display = 'block';
        } else if (estado === 'Pago') {
            formPago.style.display = 'block';
        } else if (estado === 'Pronto pago') {
            formProntoPago.style.display = 'block';
        }
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