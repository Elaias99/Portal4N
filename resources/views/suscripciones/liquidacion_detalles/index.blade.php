@extends('layouts.app')

@vite('resources/css/boleta_mensual.css')

@section('content')
<div class="container-fluid py-3 hm">

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $meses = $meses ?? [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        $proveedorFiltro = $proveedor ?? request('proveedor');
        $rutFiltro = $rut ?? request('rut');
        $tipoFiltro = $tipo ?? request('tipo');
        $anioFiltro = $anio ?? request('anio');
        $mesFiltro = $mes ?? request('mes');

        $tiposDocumento = $tiposDocumento ?? collect(['FACTURA', 'BOLETA', 'DOCUMENTO']);

        $filtrosActivos = $filtrosActivos ?? collect([
            $proveedorFiltro,
            $rutFiltro,
            $tipoFiltro,
            $anioFiltro,
            $mesFiltro,
        ])->filter(fn($v) => $v !== null && $v !== '')->count();

        $resumenPorTipo = $resumenPorTipo ?? [
            'BOLETA' => [
                'label' => 'Boletas',
                'cantidad' => 0,
                'neto_bruto' => 0,
                'total_impuesto' => 0,
                'total_final' => 0,
            ],
            'FACTURA' => [
                'label' => 'Facturas',
                'cantidad' => 0,
                'neto_bruto' => 0,
                'total_impuesto' => 0,
                'total_final' => 0,
            ],
        ];

        $resumenBoletas = $resumenPorTipo['BOLETA'] ?? [
            'label' => 'Boletas',
            'cantidad' => 0,
            'neto_bruto' => 0,
            'total_impuesto' => 0,
            'total_final' => 0,
        ];


        $contadorTipoDocumento = [
            'BOLETA' => (int) ($resumenBoletas['cantidad'] ?? 0),
            'FACTURA' => (int) ($resumenFacturas['cantidad'] ?? 0),
            'DOCUMENTO' => (int) ($resumenDocumentos['cantidad'] ?? 0),
        ];

        $labelTipoDocumento = function ($tipoDocumento) use ($contadorTipoDocumento) {
            $tipoNormalizado = mb_strtoupper(trim((string) $tipoDocumento));

            if (str_contains($tipoNormalizado, 'BOLETA')) {
                return 'Boleta Honorario(' . number_format($contadorTipoDocumento['BOLETA'], 0, ',', '.') . ')';
            }

            if (str_contains($tipoNormalizado, 'FACTURA')) {
                return 'Factura(' . number_format($contadorTipoDocumento['FACTURA'], 0, ',', '.') . ')';
            }

            if (str_contains($tipoNormalizado, 'DOCUMENTO')) {
                return 'Documento(' . number_format($contadorTipoDocumento['DOCUMENTO'], 0, ',', '.') . ')';
            }

            return $tipoDocumento;
        };

        $resumenFacturas = $resumenPorTipo['FACTURA'] ?? [
            'label' => 'Facturas',
            'cantidad' => 0,
            'neto_bruto' => 0,
            'total_impuesto' => 0,
            'total_final' => 0,
        ];

        $resumenDocumentos = $resumenPorTipo['DOCUMENTO'] ?? [
            'label' => 'Documentos',
            'cantidad' => 0,
            'neto_bruto' => 0,
            'total_impuesto' => 0,
            'total_final' => 0,
        ];

        $resumenTotalGeneral = $resumenPorTipo['TOTAL'] ?? [
            'label' => 'Total general',
            'cantidad' => 0,
            'neto_bruto' => 0,
            'total_impuesto' => 0,
            'total_final' => 0,
        ];

        $buscarValorTipoDocumento = function (string $clave) use ($tiposDocumento) {
            return collect($tiposDocumento)
                ->first(function ($tipoDocumento) use ($clave) {
                    return str_contains(
                        mb_strtoupper(trim((string) $tipoDocumento)),
                        $clave
                    );
                }) ?? $clave;
        };

        $tipoDocumentoValue = $buscarValorTipoDocumento('DOCUMENTO');
        $tipoBoletaValue = $buscarValorTipoDocumento('BOLETA');
        $tipoFacturaValue = $buscarValorTipoDocumento('FACTURA');

        $tipoSeleccionado = function ($valor) use ($tipoFiltro) {
            return mb_strtoupper(trim((string) $tipoFiltro)) === mb_strtoupper(trim((string) $valor));
        };



    @endphp

    <div class="d-flex justify-content-center align-items-center mb-4">
        <h1 class="mb-0">Liquidaciones de Suscripciones</h1>
    </div>

    <div class="row g-3 align-items-stretch mb-3">

        {{-- Generar mes completo --}}
        <div class="col-12 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="text-center mb-3">
                        <div class="fw-semibold">Generar mes completo</div>
                        <div class="small text-muted mt-1">
                            Crea las líneas mensuales desde las asignaciones existentes.
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('suscripciones.liquidacion-detalles.generar-mes') }}"
                          class="mt-auto">
                        @csrf

                        <input type="hidden" name="proveedor_actual" value="{{ $proveedorFiltro }}">

                        <div class="mb-2">
                            <label class="form-label small text-muted">Año</label>
                            <input
                                type="number"
                                name="anio_generar"
                                class="form-control form-control-sm"
                                value="{{ request('anio', 2026) }}"
                                min="2020"
                                max="2100"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Mes</label>
                            <select name="mes_generar" class="form-select form-select-sm" required>
                                @foreach($meses as $numero => $nombre)
                                    <option value="{{ $numero }}"
                                        {{ (int) request('mes', 5) === $numero ? 'selected' : '' }}>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary btn-sm w-100">
                            Generar mes completo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-semibold">Filtros de búsqueda</div>

                        @if($filtrosActivos)
                            <span class="small text-muted">
                                {{ $filtrosActivos }} activo(s)
                            </span>
                        @endif
                    </div>

                    <form method="GET"
                          action="{{ route('suscripciones.liquidacion-detalles.index') }}"
                          class="d-flex flex-column flex-grow-1">
                        <div class="row g-3 align-items-end">

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted">Proveedor</label>
                                <input
                                    type="text"
                                    name="proveedor"
                                    class="form-control form-control-sm"
                                    placeholder="Ej: ANDRES FERNANDO MUÑOZ"
                                    value="{{ $proveedorFiltro }}"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted">RUT</label>
                                <input
                                    type="text"
                                    name="rut"
                                    class="form-control form-control-sm"
                                    placeholder="Ej: 10513948-9"
                                    value="{{ $rutFiltro }}"
                                >
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted">Tipo documento</label>
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="">Todos</option>

                                    <option value="{{ $tipoDocumentoValue }}"
                                        {{ $tipoSeleccionado($tipoDocumentoValue) ? 'selected' : '' }}>
                                        Documento({{ number_format($resumenDocumentos['cantidad'], 0, ',', '.') }})
                                    </option>

                                    <option value="{{ $tipoBoletaValue }}"
                                        {{ $tipoSeleccionado($tipoBoletaValue) ? 'selected' : '' }}>
                                        Boleta Honorario({{ number_format($resumenBoletas['cantidad'], 0, ',', '.') }})
                                    </option>

                                    <option value="{{ $tipoFacturaValue }}"
                                        {{ $tipoSeleccionado($tipoFacturaValue) ? 'selected' : '' }}>
                                        Factura({{ number_format($resumenFacturas['cantidad'], 0, ',', '.') }})
                                    </option>
                                </select>
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small text-muted">Año</label>
                                <input
                                    type="number"
                                    name="anio"
                                    class="form-control form-control-sm"
                                    placeholder="2026"
                                    value="{{ $anioFiltro }}"
                                    min="2020"
                                    max="2100"
                                >
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small text-muted">Mes</label>
                                <select name="mes" class="form-select form-select-sm">
                                    <option value="">Todos</option>

                                    @foreach($meses as $numero => $nombre)
                                        <option value="{{ $numero }}"
                                            {{ (int) $mesFiltro === $numero ? 'selected' : '' }}>
                                            {{ $nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-auto pt-3">
                            <a href="{{ route('suscripciones.liquidacion-detalles.index') }}"
                               class="btn btn-outline-secondary btn-sm">
                                Limpiar
                            </a>

                            <button type="submit" class="btn btn-secondary btn-sm">
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Descargar ZIP --}}
        <div class="col-12 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="text-center mb-3">
                        <div class="fw-semibold">Descargar pre-facturas PDF</div>
                        <div class="small text-muted mt-1">
                            Genera un ZIP con una pre-factura PDF por proveedor.
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('suscripciones.liquidacion-detalles.pdf-masivo') }}"
                          class="mt-auto"
                          data-long-loader="300000">
                        @csrf

                        <input type="hidden" name="proveedor_pdf" value="{{ $proveedorFiltro }}">
                        <input type="hidden" name="rut_pdf" value="{{ $rutFiltro }}">
                        <input type="hidden" name="tipo_pdf" value="{{ $tipoFiltro }}">

                        <div class="mb-2">
                            <label class="form-label small text-muted">Año PDF</label>
                            <input
                                type="number"
                                name="anio_pdf"
                                class="form-control form-control-sm"
                                value="{{ request('anio', 2026) }}"
                                min="2020"
                                max="2100"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Mes PDF</label>
                            <select name="mes_pdf" class="form-select form-select-sm" required>
                                @foreach($meses as $numero => $nombre)
                                    <option value="{{ $numero }}"
                                        {{ (int) request('mes', 4) === $numero ? 'selected' : '' }}>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary btn-sm w-100">
                            Descargar ZIP de pre-facturas
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>


    {{-- Resumen suave --}}
    <div class="border rounded px-3 py-2 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small text-muted">
            <div>
                Pre-facturas encontradas:
                <strong class="text-dark">{{ $cantidadRegistros ?? $prefacturas->total() }}</strong>
            </div>

            <div>
                Total general:
                <strong class="text-dark">${{ number_format($resumenTotalGeneral['total_final'], 0, ',', '.') }}</strong>
            </div>
        </div>

        <div class="mt-2 pt-2 border-top small text-muted">
            <div class="row g-2">
                <div class="col-12 col-lg-3">
                    <strong class="text-dark">Boletas:</strong>
                    {{ number_format($resumenBoletas['cantidad'], 0, ',', '.') }}
                    <span class="mx-1">|</span>
                    Final:
                    <strong class="text-dark">${{ number_format($resumenBoletas['total_final'], 0, ',', '.') }}</strong>
                </div>

                <div class="col-12 col-lg-3">
                    <strong class="text-dark">Facturas:</strong>
                    {{ number_format($resumenFacturas['cantidad'], 0, ',', '.') }}
                    <span class="mx-1">|</span>
                    Final:
                    <strong class="text-dark">${{ number_format($resumenFacturas['total_final'], 0, ',', '.') }}</strong>
                </div>

                <div class="col-12 col-lg-3">
                    <strong class="text-dark">Documentos:</strong>
                    {{ number_format($resumenDocumentos['cantidad'], 0, ',', '.') }}
                    <span class="mx-1">|</span>
                    Final:
                    <strong class="text-dark">${{ number_format($resumenDocumentos['total_final'], 0, ',', '.') }}</strong>
                </div>

                <div class="col-12 col-lg-3 text-lg-end">
                    <strong class="text-dark">Total:</strong>
                    {{ number_format($resumenTotalGeneral['cantidad'], 0, ',', '.') }}
                    <span class="mx-1">|</span>
                    Final:
                    <strong class="text-dark">${{ number_format($resumenTotalGeneral['total_final'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="hm-table-wrap">
        @if($prefacturas->isEmpty())
            <div class="p-3">
                <p class="text-muted mb-0">
                    No hay pre-facturas registradas para los filtros seleccionados.
                </p>
            </div>
        @else
            <x-finanzas.plain-table>
                <thead>
                    <tr>
                        <th>Año</th>
                        <th>Mes</th>
                        <th class="text-center">Proveedor</th>
                        <th>RUT</th>
                        <th>Tipo</th>
                        <th class="text-end">Neto/Bruto</th>
                        <th class="text-end">Total Impuesto</th>
                        <th class="text-end">Final</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($prefacturas as $prefactura)
                        <tr>
                            <td class="hm-nowrap">
                                {{ $prefactura['anio'] }}
                            </td>

                            <td class="hm-nowrap">
                                {{ $prefactura['mes_nombre'] }}
                            </td>

                            <td class="text-center">
                                <a href="{{ route('suscripciones.liquidacion-detalles.show', $prefactura['detalle_id']) }}"
                                   class="fw-semibold text-decoration-none text-reset">
                                    {{ $prefactura['proveedor'] }}
                                </a>
                            </td>

                            <td class="hm-nowrap">
                                {{ $prefactura['rut'] }}
                            </td>

                            <td class="hm-nowrap">
                                {{ $prefactura['tipo'] === 'BOLETA' ? 'Boleta Honorario' : $prefactura['tipo'] }}
                            </td>

                            <td class="hm-nowrap text-end fw-semibold">
                                ${{ number_format($prefactura['neto_bruto'], 0, ',', '.') }}
                            </td>

                            <td class="hm-nowrap text-end">
                                ${{ number_format($prefactura['total_impuesto'], 1, ',', '.') }}
                            </td>

                            <td class="hm-nowrap text-end fw-semibold" title="{{ $prefactura['final'] }}">
                                ${{ number_format($prefactura['total_final'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-finanzas.plain-table>

            <div class="py-3 d-flex justify-content-center">
                {{ $prefacturas->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>

</div>
@endsection