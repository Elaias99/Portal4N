@php
    $formIdPrefix = $formIdPrefix ?? 'cobranza_compra';
    $isModalFlow = $isModalFlow ?? false;

    $bancoSinRegistro = isset($bancos)
        ? $bancos->first(fn($banco) => mb_strtolower(trim($banco->nombre)) === 'sin registro')
        : null;

    $tipoCuentaSinRegistro = isset($tipoCuentas)
        ? $tipoCuentas->first(fn($tipo) => mb_strtolower(trim($tipo->nombre)) === 'sin registro')
        : null;

    $opcionesCobranzaCompra = $opcionesCobranzaCompra ?? [];

    $camposComerciales = [
        'servicio' => 'Servicio / Detalle',
        'tipo' => 'Tipo',
        'facturacion' => 'Facturación',
        'forma_pago' => 'Forma de Pago',
    ];

    $camposGestion = [
        'zona' => 'Zona',
        'importancia' => 'Importancia',
        'responsable' => 'Responsable',
    ];

    $camposCuentaTexto = [
        'nombre_cuenta' => 'Nombre Cuenta',
        'rut_cuenta' => 'RUT Cuenta',
        'numero_cuenta' => 'Número Cuenta',
    ];

    $sectionClass = $isModalFlow
        ? 'border rounded p-3 mb-3 bg-light'
        : 'border rounded p-3 mb-4 bg-light';

    $rowClass = $isModalFlow ? 'row g-2' : 'row g-3';

    $fieldMarginClass = $isModalFlow ? 'mb-2' : 'mb-3';
@endphp

@csrf

{{-- =========================================================
IDENTIFICACIÓN
========================================================= --}}
<div class="{{ $sectionClass }}">
    <h6 class="fw-semibold mb-3">Identificación del proveedor</h6>

    <div class="{{ $rowClass }}">
        <div class="col-md-4">
            <div class="{{ $fieldMarginClass }}">
                <label for="{{ $formIdPrefix }}_rut_cliente" class="form-label">RUT Cliente</label>
                <input
                    type="text"
                    name="rut_cliente"
                    id="{{ $formIdPrefix }}_rut_cliente"
                    class="form-control @error('rut_cliente') is-invalid @enderror"
                    value="{{ old('rut_cliente', $cobranzaCompra->rut_cliente ?? '') }}"
                    required
                >
                @error('rut_cliente')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-md-8">
            <div class="{{ $fieldMarginClass }}">
                <label for="{{ $formIdPrefix }}_razon_social" class="form-label">Razón Social</label>
                <input
                    type="text"
                    name="razon_social"
                    id="{{ $formIdPrefix }}_razon_social"
                    class="form-control @error('razon_social') is-invalid @enderror"
                    value="{{ old('razon_social', $cobranzaCompra->razon_social ?? '') }}"
                    required
                >
                @error('razon_social')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

@if($isModalFlow)

    {{-- =========================================================
    CONDICIONES COMERCIALES - MODAL
    ========================================================= --}}
    <div class="{{ $sectionClass }}">
        <h6 class="fw-semibold mb-3">Condiciones comerciales</h6>

        <div class="{{ $rowClass }}">
            @foreach($camposComerciales as $campo => $label)
                @php
                    $valorActual = old($campo, $cobranzaCompra->{$campo} ?? '');
                    $valorNormalizado = mb_strtolower(trim((string) $valorActual));

                    $opcionesCampo = collect($opcionesCobranzaCompra[$campo] ?? [])
                        ->map(fn($valor) => trim((string) $valor))
                        ->filter(fn($valor) => $valor !== '')
                        ->values();

                    $existeEnOpciones = $valorActual === '' || $opcionesCampo->contains(function ($opcion) use ($valorNormalizado) {
                        return mb_strtolower(trim((string) $opcion)) === $valorNormalizado;
                    });
                @endphp

                <div class="col-md-3">
                    <div class="{{ $fieldMarginClass }}">
                        <label for="{{ $formIdPrefix }}_{{ $campo }}_select" class="form-label">{{ $label }}</label>

                        <input
                            type="hidden"
                            name="{{ $campo }}"
                            id="{{ $formIdPrefix }}_{{ $campo }}"
                            class="js-provider-dynamic-value"
                            value="{{ $valorActual }}"
                        >

                        <select
                            id="{{ $formIdPrefix }}_{{ $campo }}_select"
                            class="form-select js-provider-dynamic-select @error($campo) is-invalid @enderror"
                            data-hidden-input="#{{ $formIdPrefix }}_{{ $campo }}"
                            data-other-wrapper="#{{ $formIdPrefix }}_{{ $campo }}_otro_wrap"
                            data-other-input="#{{ $formIdPrefix }}_{{ $campo }}_otro"
                            required
                        >
                            <option value="">Seleccione {{ mb_strtolower($label) }}</option>

                            @foreach($opcionesCampo as $opcion)
                                @php
                                    $opcionNormalizada = mb_strtolower(trim((string) $opcion));
                                @endphp

                                <option
                                    value="{{ $opcion }}"
                                    {{ $existeEnOpciones && $valorNormalizado === $opcionNormalizada ? 'selected' : '' }}
                                >
                                    {{ $opcion }}
                                </option>
                            @endforeach

                            <option value="__otro__" {{ !$existeEnOpciones ? 'selected' : '' }}>
                                Otro
                            </option>
                        </select>

                        <div
                            id="{{ $formIdPrefix }}_{{ $campo }}_otro_wrap"
                            class="mt-2 js-provider-dynamic-other-wrapper"
                            style="{{ !$existeEnOpciones ? '' : 'display:none;' }}"
                        >
                            <input
                                type="text"
                                id="{{ $formIdPrefix }}_{{ $campo }}_otro"
                                class="form-control js-provider-dynamic-other"
                                data-hidden-input="#{{ $formIdPrefix }}_{{ $campo }}"
                                value="{{ !$existeEnOpciones ? $valorActual : '' }}"
                                placeholder="Ingrese {{ mb_strtolower($label) }}"
                            >
                        </div>

                        @error($campo)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach

            <div class="col-md-3">
                <div class="{{ $fieldMarginClass }}">
                    <label for="{{ $formIdPrefix }}_creditos" class="form-label">Créditos (días)</label>
                    <input
                        type="number"
                        name="creditos"
                        id="{{ $formIdPrefix }}_creditos"
                        min="0"
                        class="form-control @error('creditos') is-invalid @enderror"
                        value="{{ old('creditos', $cobranzaCompra->creditos ?? '') }}"
                        required
                    >
                    @error('creditos')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
    GESTIÓN INTERNA - MODAL
    ========================================================= --}}
    <div class="{{ $sectionClass }}">
        <h6 class="fw-semibold mb-3">Gestión interna</h6>

        <div class="{{ $rowClass }}">
            @foreach($camposGestion as $campo => $label)
                @php
                    $valorActual = old($campo, $cobranzaCompra->{$campo} ?? '');
                    $valorNormalizado = mb_strtolower(trim((string) $valorActual));

                    $opcionesCampo = collect($opcionesCobranzaCompra[$campo] ?? [])
                        ->map(fn($valor) => trim((string) $valor))
                        ->filter(fn($valor) => $valor !== '')
                        ->values();

                    $existeEnOpciones = $valorActual === '' || $opcionesCampo->contains(function ($opcion) use ($valorNormalizado) {
                        return mb_strtolower(trim((string) $opcion)) === $valorNormalizado;
                    });
                @endphp

                <div class="col-md-4">
                    <div class="{{ $fieldMarginClass }}">
                        <label for="{{ $formIdPrefix }}_{{ $campo }}_select" class="form-label">{{ $label }}</label>

                        <input
                            type="hidden"
                            name="{{ $campo }}"
                            id="{{ $formIdPrefix }}_{{ $campo }}"
                            class="js-provider-dynamic-value"
                            value="{{ $valorActual }}"
                        >

                        <select
                            id="{{ $formIdPrefix }}_{{ $campo }}_select"
                            class="form-select js-provider-dynamic-select @error($campo) is-invalid @enderror"
                            data-hidden-input="#{{ $formIdPrefix }}_{{ $campo }}"
                            data-other-wrapper="#{{ $formIdPrefix }}_{{ $campo }}_otro_wrap"
                            data-other-input="#{{ $formIdPrefix }}_{{ $campo }}_otro"
                            required
                        >
                            <option value="">Seleccione {{ mb_strtolower($label) }}</option>

                            @foreach($opcionesCampo as $opcion)
                                @php
                                    $opcionNormalizada = mb_strtolower(trim((string) $opcion));
                                @endphp

                                <option
                                    value="{{ $opcion }}"
                                    {{ $existeEnOpciones && $valorNormalizado === $opcionNormalizada ? 'selected' : '' }}
                                >
                                    {{ $opcion }}
                                </option>
                            @endforeach

                            <option value="__otro__" {{ !$existeEnOpciones ? 'selected' : '' }}>
                                Otro
                            </option>
                        </select>

                        <div
                            id="{{ $formIdPrefix }}_{{ $campo }}_otro_wrap"
                            class="mt-2 js-provider-dynamic-other-wrapper"
                            style="{{ !$existeEnOpciones ? '' : 'display:none;' }}"
                        >
                            <input
                                type="text"
                                id="{{ $formIdPrefix }}_{{ $campo }}_otro"
                                class="form-control js-provider-dynamic-other"
                                data-hidden-input="#{{ $formIdPrefix }}_{{ $campo }}"
                                value="{{ !$existeEnOpciones ? $valorActual : '' }}"
                                placeholder="Ingrese {{ mb_strtolower($label) }}"
                            >
                        </div>

                        @error($campo)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- =========================================================
    DATOS BANCARIOS - MODAL
    ========================================================= --}}
    <div class="{{ $sectionClass }}">
        <h6 class="fw-semibold mb-3">Datos bancarios</h6>

        <div class="{{ $rowClass }}">

            {{-- Campos bancarios escritos manualmente --}}
            @foreach($camposCuentaTexto as $campo => $label)
                <div class="col-md-4">
                    <div class="{{ $fieldMarginClass }}">
                        <label for="{{ $formIdPrefix }}_{{ $campo }}" class="form-label">{{ $label }}</label>

                        <input
                            type="text"
                            name="{{ $campo }}"
                            id="{{ $formIdPrefix }}_{{ $campo }}"
                            class="form-control @error($campo) is-invalid @enderror"
                            value="{{ old($campo, $cobranzaCompra->{$campo} ?? '') }}"
                            required
                        >

                        @error($campo)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach

            @php
                $bancoIdActual = old('banco_id', $cobranzaCompra->banco_id ?? '');
                $bancoOtroActual = old('banco_otro', '');
                $bancoEsOtro = $bancoIdActual === '__otro__';

                $tipoCuentaIdActual = old('tipo_cuenta_id', $cobranzaCompra->tipo_cuenta_id ?? '');
                $tipoCuentaOtroActual = old('tipo_cuenta_otro', '');
                $tipoCuentaEsOtro = $tipoCuentaIdActual === '__otro__';
            @endphp

            {{-- Banco --}}
            <div class="col-md-6">
                <div class="{{ $fieldMarginClass }}">
                    <label for="{{ $formIdPrefix }}_banco_id" class="form-label">Banco</label>

                    <select
                        name="banco_id"
                        id="{{ $formIdPrefix }}_banco_id"
                        class="form-select js-fk-other-select @error('banco_id') is-invalid @enderror"
                        data-other-wrapper="#{{ $formIdPrefix }}_banco_otro_wrap"
                        data-other-input="#{{ $formIdPrefix }}_banco_otro"
                        required
                    >
                        <option value="">Seleccione un banco</option>

                        @foreach($bancos as $banco)
                            <option value="{{ $banco->id }}"
                                {{ (string) $bancoIdActual === (string) $banco->id ? 'selected' : '' }}>
                                {{ $banco->nombre }}
                            </option>
                        @endforeach

                        <option value="__otro__" {{ $bancoEsOtro ? 'selected' : '' }}>
                            Otro
                        </option>
                    </select>

                    <div
                        id="{{ $formIdPrefix }}_banco_otro_wrap"
                        class="mt-2 js-fk-other-wrapper"
                        style="{{ $bancoEsOtro ? '' : 'display:none;' }}"
                    >
                        <input
                            type="text"
                            name="banco_otro"
                            id="{{ $formIdPrefix }}_banco_otro"
                            class="form-control @error('banco_otro') is-invalid @enderror"
                            value="{{ $bancoOtroActual }}"
                            placeholder="Ingrese banco"
                        >

                        @error('banco_otro')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    @error('banco_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Tipo de Cuenta --}}
            <div class="col-md-6">
                <div class="{{ $fieldMarginClass }}">
                    <label for="{{ $formIdPrefix }}_tipo_cuenta_id" class="form-label">Tipo de Cuenta</label>

                    <select
                        name="tipo_cuenta_id"
                        id="{{ $formIdPrefix }}_tipo_cuenta_id"
                        class="form-select js-fk-other-select @error('tipo_cuenta_id') is-invalid @enderror"
                        data-other-wrapper="#{{ $formIdPrefix }}_tipo_cuenta_otro_wrap"
                        data-other-input="#{{ $formIdPrefix }}_tipo_cuenta_otro"
                        required
                    >
                        <option value="">Seleccione un tipo de cuenta</option>

                        @foreach($tipoCuentas as $tipo)
                            <option value="{{ $tipo->id }}"
                                {{ (string) $tipoCuentaIdActual === (string) $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach

                        <option value="__otro__" {{ $tipoCuentaEsOtro ? 'selected' : '' }}>
                            Otro
                        </option>
                    </select>

                    <div
                        id="{{ $formIdPrefix }}_tipo_cuenta_otro_wrap"
                        class="mt-2 js-fk-other-wrapper"
                        style="{{ $tipoCuentaEsOtro ? '' : 'display:none;' }}"
                    >
                        <input
                            type="text"
                            name="tipo_cuenta_otro"
                            id="{{ $formIdPrefix }}_tipo_cuenta_otro"
                            class="form-control @error('tipo_cuenta_otro') is-invalid @enderror"
                            value="{{ $tipoCuentaOtroActual }}"
                            placeholder="Ingrese tipo de cuenta"
                        >

                        @error('tipo_cuenta_otro')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    @error('tipo_cuenta_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

@else

    {{-- =========================================================
    CONDICIONES COMERCIALES - VISTA NORMAL
    ========================================================= --}}
    <div class="{{ $sectionClass }}">
        <h6 class="fw-semibold mb-3">Condiciones comerciales</h6>

        <div class="{{ $rowClass }}">
            @foreach($camposComerciales as $campo => $label)
                <div class="col-md-6">
                    <div class="{{ $fieldMarginClass }}">
                        <label for="{{ $formIdPrefix }}_{{ $campo }}" class="form-label">{{ $label }}</label>

                        <input
                            type="text"
                            name="{{ $campo }}"
                            id="{{ $formIdPrefix }}_{{ $campo }}"
                            class="form-control @error($campo) is-invalid @enderror"
                            value="{{ old($campo, $cobranzaCompra->{$campo} ?? '') }}"
                            required
                        >

                        @error($campo)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach

            <div class="col-md-6">
                <div class="{{ $fieldMarginClass }}">
                    <label for="{{ $formIdPrefix }}_creditos" class="form-label">Créditos (días)</label>
                    <input
                        type="number"
                        name="creditos"
                        id="{{ $formIdPrefix }}_creditos"
                        min="0"
                        class="form-control @error('creditos') is-invalid @enderror"
                        value="{{ old('creditos', $cobranzaCompra->creditos ?? '') }}"
                        required
                    >
                    @error('creditos')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
    GESTIÓN INTERNA - VISTA NORMAL
    ========================================================= --}}
    <div class="{{ $sectionClass }}">
        <h6 class="fw-semibold mb-3">Gestión interna</h6>

        <div class="{{ $rowClass }}">
            @foreach($camposGestion as $campo => $label)
                <div class="col-md-4">
                    <div class="{{ $fieldMarginClass }}">
                        <label for="{{ $formIdPrefix }}_{{ $campo }}" class="form-label">{{ $label }}</label>

                        <input
                            type="text"
                            name="{{ $campo }}"
                            id="{{ $formIdPrefix }}_{{ $campo }}"
                            class="form-control @error($campo) is-invalid @enderror"
                            value="{{ old($campo, $cobranzaCompra->{$campo} ?? '') }}"
                            required
                        >

                        @error($campo)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- =========================================================
    DATOS BANCARIOS - VISTA NORMAL
    ========================================================= --}}
    <div class="{{ $sectionClass }}">
        <h6 class="fw-semibold mb-3">Datos bancarios</h6>

        <div class="{{ $rowClass }}">
            @foreach($camposCuentaTexto as $campo => $label)
                <div class="col-md-4">
                    <div class="{{ $fieldMarginClass }}">
                        <label for="{{ $formIdPrefix }}_{{ $campo }}" class="form-label">{{ $label }}</label>

                        <input
                            type="text"
                            name="{{ $campo }}"
                            id="{{ $formIdPrefix }}_{{ $campo }}"
                            class="form-control @error($campo) is-invalid @enderror"
                            value="{{ old($campo, $cobranzaCompra->{$campo} ?? '') }}"
                            required
                        >

                        @error($campo)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach

            <div class="col-md-6">
                <div class="{{ $fieldMarginClass }}">
                    <label for="{{ $formIdPrefix }}_banco_id" class="form-label">Banco</label>
                    <select
                        name="banco_id"
                        id="{{ $formIdPrefix }}_banco_id"
                        class="form-select @error('banco_id') is-invalid @enderror"
                        required
                    >
                        <option value="">Seleccione un banco</option>
                        @foreach($bancos as $banco)
                            <option value="{{ $banco->id }}"
                                {{ old('banco_id', $cobranzaCompra->banco_id ?? '') == $banco->id ? 'selected' : '' }}>
                                {{ $banco->nombre }}
                            </option>
                        @endforeach
                    </select>

                    @error('banco_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="{{ $fieldMarginClass }}">
                    <label for="{{ $formIdPrefix }}_tipo_cuenta_id" class="form-label">Tipo de Cuenta</label>
                    <select
                        name="tipo_cuenta_id"
                        id="{{ $formIdPrefix }}_tipo_cuenta_id"
                        class="form-select @error('tipo_cuenta_id') is-invalid @enderror"
                        required
                    >
                        <option value="">Seleccione un tipo de cuenta</option>
                        @foreach($tipoCuentas as $tipo)
                            <option value="{{ $tipo->id }}"
                                {{ old('tipo_cuenta_id', $cobranzaCompra->tipo_cuenta_id ?? '') == $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach
                    </select>

                    @error('tipo_cuenta_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

@endif

{{-- =========================================================
ACCIONES
========================================================= --}}
<div class="d-flex justify-content-end mt-3">
    @if($isModalFlow)
        <button type="button" class="btn btn-secondary me-2 js-cancel-cobranza-modal">Cancelar</button>
    @else
        <a href="{{ route('cobranzas-compras.index') }}" class="btn btn-secondary me-2">Cancelar</a>
    @endif

    @if($isModalFlow)
        <button
            type="button"
            class="btn btn-outline-secondary me-2 js-fill-proveedor-sin-registro"
            data-banco-sin-registro-id="{{ $bancoSinRegistro->id ?? '' }}"
            data-tipo-cuenta-sin-registro-id="{{ $tipoCuentaSinRegistro->id ?? '' }}"
        >
            Sin registro
        </button>
    @endif

    <button type="submit" class="btn btn-primary">{{ $btnText }}</button>
</div>