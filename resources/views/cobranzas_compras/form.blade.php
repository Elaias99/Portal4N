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

    $camposSelectDinamicos = [
        'servicio' => 'Servicio / Detalle',
        'tipo' => 'Tipo',
        'facturacion' => 'Facturación',
        'forma_pago' => 'Forma de Pago',
        'zona' => 'Zona',
        'importancia' => 'Importancia',
        'responsable' => 'Responsable',
        'nombre_cuenta' => 'Nombre Cuenta',
        'rut_cuenta' => 'RUT Cuenta',
        'numero_cuenta' => 'Número Cuenta',
    ];
@endphp

@csrf

<div class="mb-3">
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

<div class="mb-3">
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

@if($isModalFlow)

    @foreach($camposSelectDinamicos as $campo => $label)
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

        <div class="mb-3">
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
    @endforeach

@else

    @foreach($camposSelectDinamicos as $campo => $label)
        <div class="mb-3">
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
    @endforeach

@endif




<div class="mb-3">
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

<div class="mb-3">
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

<div class="mb-3">
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