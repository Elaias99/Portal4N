@extends('layouts.app')

@section('content')
<style>
    .liquidaciones-page {
        padding: 16px 8px;
    }

    .liquidaciones-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .liquidaciones-header h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 500;
    }

    .liquidaciones-header .btn {
        white-space: nowrap;
    }

    .liquidaciones-card {
        border: 1px solid #d6d8db;
        border-radius: 4px;
        background: #fff;
        margin-bottom: 12px;
    }

    .liquidaciones-card-body {
        padding: 14px;
    }

    .liquidaciones-card-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .liquidaciones-card-help {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 10px;
    }

    .liquidaciones-form label {
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .liquidaciones-form .form-control {
        height: 34px;
        font-size: 13px;
        padding: 5px 8px;
    }

    .liquidaciones-form .btn {
        height: 34px;
        font-size: 13px;
        padding: 5px 12px;
        white-space: nowrap;
    }

    .liquidaciones-table-card {
        border: 1px solid #d6d8db;
        border-radius: 4px;
        background: #fff;
    }

    .liquidaciones-table-body {
        padding: 12px;
    }

    .liquidaciones-table {
        width: 100%;
        margin-bottom: 0;
        font-size: 13px;
    }

    .liquidaciones-table th,
    .liquidaciones-table td {
        padding: 9px 10px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .liquidaciones-table th {
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .liquidaciones-table td {
        font-size: 13px;
    }

    .liquidaciones-table .text-end {
        text-align: right;
    }

    .liquidaciones-table .proveedor-col {
        width: 40%;
    }

    .liquidaciones-table .tipo-col {
        width: 15%;
    }

    .liquidaciones-table .monto-col {
        width: 15%;
    }

    .liquidaciones-pagination {
        margin-top: 12px;
        display: flex;
        justify-content: center;
    }
</style>

<div class="container-fluid liquidaciones-page">

    <div class="liquidaciones-header">
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
    <div class="liquidaciones-card">
        <div class="liquidaciones-card-body">
            <div class="liquidaciones-card-title">
                Generar mes completo
            </div>

            <div class="liquidaciones-card-help">
                Crea los registros mensuales en base a las asignaciones existentes. Esta acción registra datos en la tabla de liquidación.
            </div>

            <form method="POST" action="{{ route('suscripciones.liquidacion-detalles.generar-mes') }}" class="liquidaciones-form">
                @csrf

                <input type="hidden" name="proveedor_actual" value="{{ request('proveedor') }}">

                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
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

                    <div class="col-md-2">
                        <label class="form-label">Mes a generar</label>
                        <select name="mes_generar" class="form-control" required>
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) request('mes', 5) === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            Generar mes completo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Buscador / filtros --}}
    <div class="liquidaciones-card">
        <div class="liquidaciones-card-body">
            <div class="liquidaciones-card-title">
                Filtros
            </div>

            <form method="GET" action="{{ route('suscripciones.liquidacion-detalles.index') }}" class="liquidaciones-form">
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

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Buscar
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('suscripciones.liquidacion-detalles.index') }}" class="btn btn-secondary w-100">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Generar PDFs masivos --}}
    <div class="liquidaciones-card">
        <div class="liquidaciones-card-body">
            <div class="liquidaciones-card-title">
                Descargar pre-facturas PDF
            </div>

            <div class="liquidaciones-card-help">
                Genera un archivo ZIP con una pre-factura PDF por cada proveedor del período seleccionado.
                Si hay un proveedor escrito en el filtro, sólo se generarán PDFs para ese proveedor.
            </div>

            <form method="POST" action="{{ route('suscripciones.liquidacion-detalles.pdf-masivo') }}" class="liquidaciones-form">
                @csrf

                <input type="hidden" name="proveedor_pdf" value="{{ request('proveedor') }}">

                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
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

                    <div class="col-md-2">
                        <label class="form-label">Mes PDF</label>
                        <select name="mes_pdf" class="form-control" required>
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) request('mes', 4) === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-danger w-100">
                            Descargar ZIP de pre-facturas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="liquidaciones-table-card">
        <div class="liquidaciones-table-body">

            <table class="table table-bordered table-striped align-middle liquidaciones-table">
                <thead>
                    <tr>
                        <th class="proveedor-col">Proveedor</th>
                        <th class="tipo-col">Tipo</th>
                        <th class="monto-col text-end">Neto/Bruto</th>
                        <th class="monto-col text-end">Total Impuesto</th>
                        <th class="monto-col text-end">Neto</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($detalles as $detalle)
                        @php
                            $calculo = $calculosDetalle[$detalle->id] ?? null;
                        @endphp

                        <tr>
                            <td>
                                <a href="{{ route('suscripciones.liquidacion-detalles.show', $detalle->id) }}"
                                   class="text-decoration-none fw-bold text-primary">
                                    {{ $detalle->asignacion?->suscripcionProveedor?->cobranzaCompra?->razon_social ?? '—' }}
                                </a>
                            </td>

                            <td>
                                @php
                                    $tipo = $detalle->asignacion?->suscripcionProveedor?->tipo;
                                @endphp

                                {{ $tipo === 'BOLETA' ? 'Boleta Honorario' : ($tipo ?? '—') }}
                            </td>

                            <td class="text-end">
                                ${{ number_format($calculo['neto_bruto'] ?? 0, 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                ${{ number_format($calculo['total_impuesto'] ?? 0, 1, ',', '.') }}
                            </td>

                            <td class="text-end fw-bold">
                                ${{ number_format($calculo['liquido'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                No hay detalles mensuales registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="liquidaciones-pagination">
                {{ $detalles->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>

        </div>
    </div>

</div>
@endsection