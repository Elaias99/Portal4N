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
                            <option value="Factory" {{ $doc->status == 'Factory' ? 'selected' : '' }}>Factory</option>
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

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Saldo pendiente
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

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

                {{-- FORMULARIO DE FACTORY --}}
                <form action="{{ route('documentos.factory.store', $doc->id) }}"
                    method="POST"
                    id="form-factory-{{ $doc->id }}"
                    style="display: {{ $doc->status == 'Factory' ? 'block' : 'none' }};"
                    data-tiene-factory="{{ $doc->factoryRegistro ? '1' : '0' }}">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">Saldo pendiente</label>
                        <input type="text"
                            class="form-control form-control-sm"
                            value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                            readonly>
                    </div>

                    @if($doc->factoryRegistro)
                        <div class="alert alert-info py-2 px-3 small">
                            Este documento ya tiene un registro <strong>Factory</strong> asociado.
                            Para revertirlo, elimínalo desde el detalle del documento.
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">Nombre Factory / Banco</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="{{ $doc->factoryRegistro->banco?->nombre ?? 'Sin banco' }}"
                                readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">RUT Factory</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="{{ $doc->factoryRegistro->rut_factory }}"
                                readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">Cesión</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="{{ $doc->factoryRegistro->cesion ?? '-' }}"
                                readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">Fecha Factory</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="{{ $doc->factoryRegistro->fecha_factory ? \Carbon\Carbon::parse($doc->factoryRegistro->fecha_factory)->format('d-m-Y') : '-' }}"
                                readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">Monto documento / saldo cedido</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="${{ number_format($doc->factoryRegistro->monto ?? 0, 0, ',', '.') }}"
                                readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">Saldo líquido</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="${{ number_format($doc->factoryRegistro->saldo_liquido ?? 0, 0, ',', '.') }}"
                                readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">Diferencia</label>
                            <input type="text"
                                class="form-control form-control-sm"
                                value="${{ number_format($doc->factoryRegistro->diferencia ?? 0, 0, ',', '.') }}"
                                readonly>
                        </div>
                    @else
                        <div class="form-group mb-3">
                            <label for="banco-factory-{{ $doc->id }}" class="form-label small text-muted">
                                Nombre Factory / Banco
                            </label>

                            <select name="banco_id"
                                    id="banco-factory-{{ $doc->id }}"
                                    class="form-select form-select-sm @error('banco_id') is-invalid @enderror"
                                    onchange="toggleBancoFactoryOtro({{ $doc->id }})"
                                    required>
                                <option value="">Seleccione banco / factory</option>

                                @foreach(($bancos ?? collect()) as $banco)
                                    <option value="{{ $banco->id }}">
                                        {{ $banco->nombre }}
                                    </option>
                                @endforeach

                                <option value="__otro__">Otro</option>
                            </select>

                            <div id="banco-factory-otro-wrapper-{{ $doc->id }}"
                                class="mt-2"
                                style="display:none;">
                                <label for="banco-factory-otro-{{ $doc->id }}" class="form-label small text-muted">
                                    Nombre nuevo banco / Factory
                                </label>

                                <input type="text"
                                    name="banco_otro"
                                    id="banco-factory-otro-{{ $doc->id }}"
                                    class="form-control form-control-sm @error('banco_otro') is-invalid @enderror"
                                    placeholder="Ingrese nombre del banco o Factory">
                            </div>

                            @error('banco_id')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror

                            @error('banco_otro')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="rut-factory-{{ $doc->id }}" class="form-label small text-muted">
                                RUT Factory
                            </label>

                            <input type="text"
                                name="rut_factory"
                                id="rut-factory-{{ $doc->id }}"
                                class="form-control form-control-sm @error('rut_factory') is-invalid @enderror"
                                placeholder="Ej: 76000000-0"
                                required>

                            @error('rut_factory')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="cesion-factory-{{ $doc->id }}" class="form-label small text-muted">
                                Cesión
                            </label>

                            <input type="text"
                                name="cesion"
                                id="cesion-factory-{{ $doc->id }}"
                                class="form-control form-control-sm @error('cesion') is-invalid @enderror"
                                placeholder="Ej: 665162"
                                required>

                            @error('cesion')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">
                                Fecha Factory
                            </label>

                            <input type="date"
                                class="form-control form-control-sm"
                                value="{{ now()->format('Y-m-d') }}"
                                readonly>

                            <small class="text-muted">
                                La fecha se registrará automáticamente con la fecha actual.
                            </small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="saldo-liquido-factory-{{ $doc->id }}" class="form-label small text-muted">
                                Saldo líquido
                            </label>

                            <input type="text"
                                name="saldo_liquido"
                                id="saldo-liquido-factory-{{ $doc->id }}"
                                class="form-control form-control-sm @error('saldo_liquido') is-invalid @enderror"
                                data-saldo="{{ (int) $doc->saldo_pendiente }}"
                                placeholder="Ej: {{ (int) $doc->saldo_pendiente }}"
                                oninput="calcularDiferenciaFactoryIndividual({{ $doc->id }})"
                                required>

                            @error('saldo_liquido')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">
                                Diferencia
                            </label>

                            <input type="text"
                                id="diferencia-factory-{{ $doc->id }}"
                                class="form-control form-control-sm"
                                value="$0"
                                readonly>

                            <small class="text-muted">
                                Diferencia entre el saldo pendiente y el saldo líquido informado.
                            </small>
                        </div>

                        <div class="alert alert-info py-1 px-2 small">
                            Al registrar Factory, el saldo pendiente quedará automáticamente en <strong>0</strong>.
                        </div>
                    @endif
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
{{-- === SCRIPT === --}}
<script>
    function toggleEstadoFields(id) {
        const estado = document.getElementById('status-' + id).value;

        const formAbono = document.getElementById('form-abono-' + id);
        const formCruce = document.getElementById('form-cruce-' + id);
        const formPago = document.getElementById('form-pago-' + id);
        const formProntoPago = document.getElementById('form-prontopago-' + id);
        const formFactory = document.getElementById('form-factory-' + id);

        formAbono.style.display = 'none';
        formCruce.style.display = 'none';
        formPago.style.display = 'none';
        formProntoPago.style.display = 'none';

        if (formFactory) {
            formFactory.style.display = 'none';
        }

        if (estado === 'Abono') {
            formAbono.style.display = 'block';
        } else if (estado === 'Cruce') {
            formCruce.style.display = 'block';
        } else if (estado === 'Pago') {
            formPago.style.display = 'block';
        } else if (estado === 'Pronto pago') {
            formProntoPago.style.display = 'block';
        } else if (estado === 'Factory') {
            if (formFactory) {
                formFactory.style.display = 'block';
            }
        }
    }

    function toggleBancoFactoryOtro(id) {
        const selectBanco = document.getElementById('banco-factory-' + id);
        const wrapperOtro = document.getElementById('banco-factory-otro-wrapper-' + id);
        const inputOtro = document.getElementById('banco-factory-otro-' + id);

        if (!selectBanco || !wrapperOtro || !inputOtro) {
            return;
        }

        if (selectBanco.value === '__otro__') {
            wrapperOtro.style.display = 'block';
            inputOtro.required = true;
        } else {
            wrapperOtro.style.display = 'none';
            inputOtro.required = false;
            inputOtro.value = '';
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
        } else if (estado === 'Factory') {
            const formFactory = document.getElementById('form-factory-' + id);

            if (!formFactory) {
                return;
            }

            if (formFactory.dataset.tieneFactory === '1') {
                alert('Este documento ya tiene un registro Factory. Para revertirlo, elimínalo desde el detalle del documento.');
                return;
            }

            toggleBancoFactoryOtro(id);

            if (typeof formFactory.reportValidity === 'function' && !formFactory.reportValidity()) {
                return;
            }

            formFactory.submit();
        } else {
            document.getElementById('form-status-' + id).submit();
        }
    }
</script>

<script>
    function normalizarMontoFactoryIndividual(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }

        return Number(String(value).replace(/[^\d]/g, '')) || 0;
    }

    function formatearCLPFactoryIndividual(value) {
        return Number(value || 0).toLocaleString('es-CL', {
            style: 'currency',
            currency: 'CLP',
            maximumFractionDigits: 0
        });
    }

    function calcularDiferenciaFactoryIndividual(id) {
        const inputSaldoLiquido = document.getElementById('saldo-liquido-factory-' + id);
        const inputDiferencia = document.getElementById('diferencia-factory-' + id);

        if (!inputSaldoLiquido || !inputDiferencia) {
            return;
        }

        const saldoPendiente = Number(inputSaldoLiquido.dataset.saldo || 0);
        const saldoLiquido = normalizarMontoFactoryIndividual(inputSaldoLiquido.value);
        const diferencia = Math.max(saldoPendiente - saldoLiquido, 0);

        inputDiferencia.value = formatearCLPFactoryIndividual(diferencia);

        if (saldoLiquido > saldoPendiente) {
            inputSaldoLiquido.setCustomValidity('El saldo líquido no puede ser mayor al saldo pendiente.');
        } else {
            inputSaldoLiquido.setCustomValidity('');
        }
    }

    function toggleBancoFactoryOtro(id) {
        const select = document.getElementById('banco-factory-' + id);
        const wrapper = document.getElementById('banco-factory-otro-wrapper-' + id);
        const input = document.getElementById('banco-factory-otro-' + id);

        if (!select || !wrapper || !input) {
            return;
        }

        if (select.value === '__otro__') {
            wrapper.style.display = 'block';
            input.required = true;
        } else {
            wrapper.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }
</script>