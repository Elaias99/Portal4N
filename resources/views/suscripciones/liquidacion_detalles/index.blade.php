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

                        <button type="submit" class="btn btn-success btn-sm w-100">
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
                            <span class="hm-summary-badge">
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

                                    @foreach($tiposDocumento as $tipoDocumento)
                                        <option value="{{ $tipoDocumento }}"
                                            {{ $tipoFiltro === $tipoDocumento ? 'selected' : '' }}>
                                            {{ $tipoDocumento === 'BOLETA' ? 'Boleta Honorario' : $tipoDocumento }}
                                        </option>
                                    @endforeach
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

                            <button type="submit" class="btn btn-success btn-sm">
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
                          class="mt-auto">
                        @csrf

                        <input type="hidden" name="proveedor_pdf" value="{{ $proveedorFiltro }}">

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

                        <button type="submit" class="btn btn-danger btn-sm w-100">
                            Descargar ZIP de pre-facturas
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
        <div class="text-muted small">
            Pre-facturas encontradas:
            <strong>{{ $cantidadRegistros ?? $prefacturas->total() }}</strong>
        </div>

        <div class="text-muted small">
            Total período:
            <strong>${{ number_format($totalPeriodo ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>

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
                        <th>Proveedor</th>
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

                            <td>
                                <a href="{{ route('suscripciones.liquidacion-detalles.show', $prefactura['detalle_id']) }}"
                                   class="fw-semibold text-decoration-none">
                                    {{ $prefactura['proveedor'] }}
                                </a>
                            </td>

                            <td class="hm-nowrap">
                                {{ $prefactura['rut'] }}
                            </td>

                            <td class="hm-nowrap">
                                @if($prefactura['tipo'] === 'BOLETA')
                                    <span class="hm-chip hm-chip-ok">
                                        Boleta Honorario
                                    </span>
                                @elseif($prefactura['tipo'] === 'FACTURA')
                                    <span class="hm-chip hm-chip-info">
                                        FACTURA
                                    </span>
                                @else
                                    <span class="hm-chip">
                                        {{ $prefactura['tipo'] }}
                                    </span>
                                @endif
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