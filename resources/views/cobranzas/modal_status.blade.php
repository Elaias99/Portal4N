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
                               value="{{ $doc->estado_visible === 'Factory' ? 'Factoring' : $doc->estado_visible }}"
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
                            <option value="Factory" {{ $doc->status == 'Factory' ? 'selected' : '' }}>Factoring</option>
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

                {{-- FORMULARIO DE FACTORING --}}
                @php
                    $factoriesRegistrados = $doc->factories ?? collect();
                    $cantidadFactoriesRegistrados = $factoriesRegistrados->count();
                    $ultimoFactoryRegistrado = $factoriesRegistrados
                        ->sortByDesc('created_at')
                        ->first();
                @endphp

                <form action="{{ route('documentos.factory.store', $doc->id) }}"
                      method="POST"
                      id="form-factory-{{ $doc->id }}"
                      class="js-form-factory-individual"
                      data-documento-id="{{ $doc->id }}"
                      data-monto="{{ (int) $doc->saldo_pendiente }}"
                      data-tiene-factory="0"
                      style="display: {{ $doc->status == 'Factory' ? 'block' : 'none' }};">
                    @csrf

                    @if($cantidadFactoriesRegistrados > 0)
                        <div class="alert alert-info py-2 px-3 small mb-3">
                            Este documento ya registra
                            <strong>
                                {{ $cantidadFactoriesRegistrados }}
                                {{ $cantidadFactoriesRegistrados === 1 ? 'operación Factoring' : 'operaciones Factoring' }}
                            </strong>.

                            La nueva operación se aplicará sobre el saldo pendiente vigente de
                            <strong>${{ number_format((int) $doc->saldo_pendiente, 0, ',', '.') }}</strong>.

                            @if($ultimoFactoryRegistrado)
                                <div class="mt-1">
                                    Última operación registrada:
                                    cesión <strong>{{ $ultimoFactoryRegistrado->cesion ?? '—' }}</strong>,
                                    fecha
                                    <strong>
                                        {{ $ultimoFactoryRegistrado->fecha_factory
                                            ? \Carbon\Carbon::parse($ultimoFactoryRegistrado->fecha_factory)->format('d-m-Y')
                                            : '—' }}
                                    </strong>.
                                </div>
                            @endif

                            <div class="mt-1">
                                Para consultar o eliminar operaciones anteriores, ingresa al detalle del documento.
                            </div>
                        </div>
                    @endif

                    {{-- MONTO DEL DOCUMENTO / SALDO CEDIDO --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Monto documento / saldo cedido
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format((int) $doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    {{-- BANCO / ENTIDAD FACTORING --}}
                    <div class="form-group mb-3">
                        <label for="banco-factory-{{ $doc->id }}"
                               class="form-label small text-muted">
                            Entidad Factoring / Banco
                        </label>

                        <select name="banco_id"
                                id="banco-factory-{{ $doc->id }}"
                                class="form-select form-select-sm @error('banco_id') is-invalid @enderror"
                                onchange="toggleBancoFactoryOtro({{ $doc->id }})"
                                required>
                            <option value="">
                                Seleccione entidad / banco
                            </option>

                            @foreach(($bancos ?? collect()) as $banco)
                                <option value="{{ $banco->id }}"
                                    {{ (string) old('banco_id') === (string) $banco->id ? 'selected' : '' }}>
                                    {{ $banco->nombre }}
                                </option>
                            @endforeach

                            <option value="__otro__"
                                {{ old('banco_id') === '__otro__' ? 'selected' : '' }}>
                                Otra entidad
                            </option>
                        </select>

                        @error('banco_id')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                        <div id="banco-factory-otro-wrapper-{{ $doc->id }}"
                             class="mt-2"
                             style="display: {{ old('banco_id') === '__otro__' ? 'block' : 'none' }};">

                            <label for="banco-factory-otro-{{ $doc->id }}"
                                   class="form-label small text-muted">
                                Nueva entidad Factoring / Banco
                            </label>

                            <input type="text"
                                   name="banco_otro"
                                   id="banco-factory-otro-{{ $doc->id }}"
                                   class="form-control form-control-sm @error('banco_otro') is-invalid @enderror"
                                   value="{{ old('banco_otro') }}"
                                   placeholder="Ingrese nueva entidad / banco"
                                   {{ old('banco_id') === '__otro__' ? 'required' : '' }}>

                            @error('banco_otro')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    {{-- CESIÓN --}}
                    <div class="form-group mb-3">
                        <label for="cesion-factory-{{ $doc->id }}"
                               class="form-label small text-muted">
                            N° Cesión
                        </label>

                        <input type="text"
                               name="cesion"
                               id="cesion-factory-{{ $doc->id }}"
                               class="form-control form-control-sm @error('cesion') is-invalid @enderror"
                               value="{{ old('cesion') }}"
                               placeholder="Ej: 665162"
                               required>

                        @error('cesion')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- FECHA OPERACIÓN --}}
                    <div class="form-group mb-3">
                        <label for="fecha-factory-{{ $doc->id }}"
                               class="form-label small text-muted">
                            Fecha operación
                        </label>

                        <input type="date"
                               name="fecha_factory"
                               id="fecha-factory-{{ $doc->id }}"
                               class="form-control form-control-sm @error('fecha_factory') is-invalid @enderror"
                               value="{{ old('fecha_factory', now()->format('Y-m-d')) }}"
                               required>

                        @error('fecha_factory')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- MONTO LÍQUIDO --}}
                    <div class="form-group mb-3">
                        <label for="saldo-liquido-factory-{{ $doc->id }}"
                               class="form-label small text-muted">
                            Monto Líquido
                        </label>

                        <input type="number"
                               name="saldo_liquido"
                               id="saldo-liquido-factory-{{ $doc->id }}"
                               class="form-control form-control-sm js-factory-individual-saldo-liquido @error('saldo_liquido') is-invalid @enderror"
                               data-documento-id="{{ $doc->id }}"
                               value="{{ old('saldo_liquido') }}"
                               min="0"
                               max="{{ (int) $doc->saldo_pendiente }}"
                               step="1"
                               placeholder="Ej: {{ (int) $doc->saldo_pendiente }}"
                               required>

                        @error('saldo_liquido')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- MONTO NO ANTICIPADO --}}
                    <div class="form-group mb-3">
                        <label for="monto-no-anticipado-factory-{{ $doc->id }}"
                               class="form-label small text-muted">
                            Monto No Anticipado
                        </label>

                        <input type="number"
                               name="monto_no_anticipado"
                               id="monto-no-anticipado-factory-{{ $doc->id }}"
                               class="form-control form-control-sm js-factory-individual-monto-no-anticipado @error('monto_no_anticipado') is-invalid @enderror"
                               data-documento-id="{{ $doc->id }}"
                               value="{{ old('monto_no_anticipado') }}"
                               min="0"
                               max="{{ (int) $doc->saldo_pendiente }}"
                               step="1"
                               placeholder="Ej: 0"
                               required>

                        @error('monto_no_anticipado')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- DIFERENCIA DE PRECIO CALCULADA --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Diferencia de Precio
                        </label>

                        <input type="text"
                               id="diferencia-precio-factory-preview-{{ $doc->id }}"
                               class="form-control form-control-sm js-factory-individual-diferencia-precio"
                               value="—"
                               readonly>

                        <small class="text-muted">
                            Monto documento - Monto Líquido - Monto No Anticipado.
                        </small>
                    </div>

                    {{-- COMISIÓN TOTAL --}}
                    <div class="form-group mb-3">
                        <label for="comision-total-factory-{{ $doc->id }}"
                               class="form-label small text-muted">
                            Comisión Total (1)
                        </label>

                        <input type="number"
                               name="comision_total"
                               id="comision-total-factory-{{ $doc->id }}"
                               class="form-control form-control-sm js-factory-individual-comision-total @error('comision_total') is-invalid @enderror"
                               data-documento-id="{{ $doc->id }}"
                               value="{{ old('comision_total') }}"
                               min="0"
                               step="1"
                               placeholder="Ej: 71118"
                               required>

                        @error('comision_total')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- MONTO A RECIBIR CALCULADO --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Monto a Recibir
                        </label>

                        <input type="text"
                               id="monto-a-recibir-factory-preview-{{ $doc->id }}"
                               class="form-control form-control-sm js-factory-individual-monto-a-recibir"
                               value="—"
                               readonly>

                        <small class="text-muted">
                            Monto Líquido de la operación - Comisión Total - Diferencia de Precio.
                        </small>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0">
                        Al registrar Factoring, la
                        <strong>Diferencia de Precio</strong> quedará como saldo pendiente
                        vigente del documento. El <strong>Monto a Recibir</strong> se almacenará
                        como parte de esta operación.
                    </div>
                </form>
            </div>

            {{-- === FOOTER === --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    Cancelar
                </button>

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
        } else if (estado === 'Factory' && formFactory) {
            formFactory.style.display = 'block';
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
            return;
        }

        if (estado === 'Cruce') {
            document.getElementById('form-cruce-' + id).submit();
            return;
        }

        if (estado === 'Pago') {
            document.getElementById('form-pago-' + id).submit();
            return;
        }

        if (estado === 'Pronto pago') {
            document.getElementById('form-prontopago-' + id).submit();
            return;
        }

        if (estado === 'Factory') {
            const formFactory = document.getElementById('form-factory-' + id);

            if (!formFactory) {
                return;
            }

            toggleBancoFactoryOtro(id);

            /*
            |--------------------------------------------------------------------------
            | Registrar una nueva operación Factoring
            |--------------------------------------------------------------------------
            | Ya no se bloquea por la existencia de Factorings anteriores.
            | El backend valida saldo pendiente y movimientos de cierre.
            |--------------------------------------------------------------------------
            */
            if (typeof formFactory.reportValidity === 'function' && !formFactory.reportValidity()) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | requestSubmit dispara la validación definida en
            | resources/js/cobranzas_documentos.js antes de enviar el formulario.
            |--------------------------------------------------------------------------
            */
            if (typeof formFactory.requestSubmit === 'function') {
                formFactory.requestSubmit();
            } else {
                formFactory.submit();
            }

            return;
        }

        document.getElementById('form-status-' + id).submit();
    }
</script>