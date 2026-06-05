@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Liquidaciones de Suscripciones</h1>

        <a href="{{ route('suscripciones.liquidacion-detalles.create') }}" class="btn btn-primary">
            Crear detalle mensual
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
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
    @endphp

    {{-- Generar mes completo --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Generar mes completo</strong>
        </div>

        <div class="card-body">
            <p class="text-muted mb-3">
                Crea los registros mensuales en base a las asignaciones existentes. Esta acción registra datos en la tabla de liquidación.
            </p>

            <form method="POST" action="{{ route('suscripciones.liquidacion-detalles.generar-mes') }}">
                @csrf

                <input type="hidden" name="proveedor_actual" value="{{ request('proveedor') }}">

                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Año a generar</label>
                        <input 
                            type="number"
                            name="anio_generar"
                            class="form-control"
                            value="{{ request('anio', 2026) }}"
                            min="2020"
                            max="2100"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Mes a generar</label>
                        <select name="mes_generar" class="form-control" required>
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) request('mes', 5) === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success">
                            Generar mes completo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Filtros</strong>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('suscripciones.liquidacion-detalles.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Buscar proveedor</label>
                        <input 
                            type="text"
                            name="proveedor"
                            class="form-control"
                            placeholder="Ej: ANDRES FERNANDO MUÑOZ"
                            value="{{ request('proveedor') }}"
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Año</label>
                        <input 
                            type="number"
                            name="anio"
                            class="form-control"
                            placeholder="2026"
                            value="{{ request('anio') }}"
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-control">
                            <option value="">Todos</option>
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) request('mes') === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            Buscar
                        </button>

                        <a href="{{ route('suscripciones.liquidacion-detalles.index') }}" class="btn btn-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Generar PDFs masivos --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Descargar pre-facturas PDF</strong>
        </div>

        <div class="card-body">
            <p class="text-muted mb-3">
                Genera un archivo ZIP con una pre-factura PDF por cada proveedor del período seleccionado.
                Si hay un proveedor escrito en el filtro, sólo se generarán PDFs para ese proveedor.
            </p>

            <form method="POST" action="{{ route('suscripciones.liquidacion-detalles.pdf-masivo') }}">
                @csrf

                <input type="hidden" name="proveedor_pdf" value="{{ request('proveedor') }}">

                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Año PDF</label>
                        <input 
                            type="number"
                            name="anio_pdf"
                            class="form-control"
                            value="{{ request('anio', 2026) }}"
                            min="2020"
                            max="2100"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Mes PDF</label>
                        <select name="mes_pdf" class="form-control" required>
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) request('mes', 4) === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-danger">
                            Descargar ZIP de pre-facturas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>








    {{-- Tabla --}}
    <div class="card">
        <div class="card-header">
            <strong>Listado de pre-facturas</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Mes</th>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Tipo</th>
                            {{-- <th class="text-end">Líneas</th> --}}
                            <th class="text-end">Neto/Bruto</th>
                            <th class="text-end">Total Impuesto</th>
                            <th class="text-end">Final</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($prefacturas as $prefactura)
                            <tr>
                                <td>{{ $prefactura['anio'] }}</td>

                                <td>{{ $prefactura['mes_nombre'] }}</td>

                                <td>
                                    <a href="{{ route('suscripciones.liquidacion-detalles.show', $prefactura['detalle_id']) }}"
                                    class="text-decoration-none fw-bold text-primary">
                                        {{ $prefactura['proveedor'] }}
                                    </a>
                                </td>

                                <td>
                                    {{ $prefactura['rut'] }}
                                </td>

                                <td>
                                    {{ $prefactura['tipo'] === 'BOLETA' ? 'Boleta Honorario' : $prefactura['tipo'] }}
                                </td>


                                <td class="text-end">
                                    ${{ number_format($prefactura['neto_bruto'], 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    ${{ number_format($prefactura['total_impuesto'], 1, ',', '.') }}
                                </td>

                                <td class="text-end fw-bold" title="{{ $prefactura['final'] }}">
                                    ${{ number_format($prefactura['total_final'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">
                                    No hay pre-facturas registradas para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center">
                {{ $prefacturas->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>








</div>
@endsection