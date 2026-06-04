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
        <div class="card-body">
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

    {{-- Buscador / filtros --}}
    <form method="GET" action="{{ route('suscripciones.liquidacion-detalles.index') }}" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
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

            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    Buscar
                </button>

                <a href="{{ route('suscripciones.liquidacion-detalles.index') }}" class="btn btn-secondary">
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    {{-- Totales --}}
    {{-- <div class="row mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <strong>Total registros:</strong>
                    {{ $cantidadRegistros ?? 0 }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <strong>Total período:</strong>
                    ${{ number_format($totalPeriodo ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div> --}}

    {{-- Tabla --}}
    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Mes</th>
                            <th>Proveedor</th>
                            <th>Transportista</th>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Punto 1</th>
                            <th>Punto 2</th>
                            <th class="text-end">Costo</th>
                            <th class="text-end">Q Calendario</th>
                            <th class="text-end">Q Inasistencia</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>

                    <tbody>



                        @forelse($detalles as $detalle)
                            @php
                                $esValorFijo = str_ends_with(mb_strtoupper(trim($detalle->codigo)), '.COM');
                            @endphp

                            <tr>
                                <td>{{ $detalle->anio }}</td>

                                <td>
                                    {{ $meses[$detalle->mes] ?? $detalle->mes }}
                                </td>

                                <td>
                                    {{ $detalle->asignacion?->suscripcionProveedor?->cobranzaCompra?->razon_social ?? '—' }}
                                </td>

                                <td>
                                    {{ $detalle->asignacion?->transportista?->nombre_transportista ?? '—' }}
                                </td>

                                <td>
                                    <a href="{{ route('suscripciones.liquidacion-detalles.edit', $detalle->id) }}"
                                    class="text-decoration-none fw-bold text-primary">
                                        {{ $detalle->codigo }}
                                    </a>
                                </td>

                                <td>
                                    {{ $detalle->asignacion?->servicio ?? '—' }}
                                </td>

                                <td>
                                    {{ $detalle->asignacion?->punto_1 ?? '—' }}
                                </td>

                                <td>
                                    {{ $detalle->asignacion?->punto_2 ?? '—' }}
                                </td>

                                <td class="text-end">
                                    ${{ number_format($detalle->costo, 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    @if($esValorFijo)
                                        <span class="badge bg-secondary">Fijo</span>
                                    @else
                                        {{ $detalle->q_calendario }}
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($esValorFijo)
                                        —
                                    @else
                                        {{ $detalle->q_inasistencia }}
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($esValorFijo)
                                        —
                                    @else
                                        {{ $detalle->cantidad }}
                                    @endif
                                </td>

                                <td class="text-end fw-bold">
                                    ${{ number_format($detalle->total, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center">
                                    No hay detalles mensuales registrados.
                                </td>
                            </tr>
                        @endforelse










                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $detalles->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>

        </div>
    </div>

</div>
@endsection