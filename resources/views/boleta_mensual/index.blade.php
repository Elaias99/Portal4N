@extends('layouts.app')

@section('content')
<div class="container">

    <h1 class="mb-4">Honorarios Mensuales Recibidos</h1>

    {{-- =========================
        1️⃣ FORMULARIO IMPORTACIÓN
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            Importar archivo SII
        </div>
        <div class="card-body">
            <form action="{{ route('honorarios.mensual.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="archivo" class="form-label">
                        Archivo SII (file_informeMensualREC)
                    </label>
                    <input type="file" name="archivo" id="archivo" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Importar y previsualizar
                </button>
            </form>
        </div>
    </div>

    {{-- =========================
        MENSAJES
    ========================== --}}
    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    {{-- =========================
        2️⃣ PREVISUALIZACIÓN
    ========================== --}}
    @if(session('preview'))
        @php($preview = session('preview'))

        <div class="card mb-5 border-warning">
            <div class="card-header bg-warning">
                <strong>Previsualización del archivo (sin guardar)</strong>
            </div>
            <div class="card-body">

                {{-- Meta --}}
                <p><strong>Contribuyente:</strong> {{ $preview['meta']['razon_social'] }}</p>
                <p><strong>RUT:</strong> {{ $preview['meta']['rut_contribuyente'] }}</p>
                <p>
                    <strong>Periodo:</strong>
                    {{ $preview['meta']['mes'] }} / {{ $preview['meta']['anio'] }}
                </p>

                {{-- Tabla registros --}}
                <table class="table table-sm table-bordered mt-3">
                    <thead class="table-secondary">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Rut Emisor</th>
                            <th>Razón Social</th>
                            <th>Soc. Prof.</th>
                            <th>Bruto</th>
                            <th>Retenido</th>
                            <th>Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['registros'] as $r)
                            <tr>
                                <td>{{ $r['folio'] }}</td>
                                <td>{{ $r['fecha_emision'] }}</td>
                                <td>{{ $r['estado'] }}</td>
                                <td>{{ $r['rut_emisor'] }}</td>
                                <td>{{ $r['razon_social_emisor'] }}</td>
                                <td>{{ $r['sociedad_profesional'] ? 'SI' : 'NO' }}</td>


                                <td class="text-end">
                                    {{ number_format($r['monto_bruto'], 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($r['monto_retenido'], 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($r['monto_pagado'], 0, ',', '.') }}
                                </td>


                            </tr>
                        @endforeach

                        {{-- FILA DE TOTALES (estilo Excel) --}}
                        <tr class="table-light fw-bold">
                            <td colspan="6">Totales</td>
                            <td class="text-end">
                                {{ number_format($preview['totales']['bruto'], 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                {{ number_format($preview['totales']['retenido'], 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                {{ number_format($preview['totales']['pagado'], 0, ',', '.') }}
                            </td>
                        </tr>


                    </tbody>
                </table>

                {{-- Botón guardar (siguiente paso) --}}
                <form action="{{ route('honorarios.mensual.store') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
                    <button class="btn btn-success">
                        Confirmar y guardar
                    </button>
                </form>



            </div>
        </div>
    @endif

    {{-- =========================
        3️⃣ REGISTROS GUARDADOS
    ========================== --}}
    <div class="card">
        <div class="card-header">
            Registros almacenados
        </div>
        <div class="card-body">

            @if($registros->isEmpty())
                <p>No hay registros cargados.</p>
            @else
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Mes</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Emisor</th>
                            <th>Bruto</th>
                            <th>Retenido</th>
                            <th>Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $r)
                            <tr>
                                <td>{{ $r->anio }}</td>
                                <td>{{ $r->mes }}</td>
                                <td>{{ $r->folio }}</td>
                                <td>{{ $r->fecha_emision->format('d-m-Y') }}</td>
                                <td>{{ $r->estado }}</td>
                                <td>{{ $r->razon_social_emisor }}</td>
                                <td>{{ number_format($r->monto_bruto, 0, ',', '.') }}</td>
                                <td>{{ number_format($r->monto_retenido, 0, ',', '.') }}</td>
                                <td>{{ number_format($r->monto_pagado, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        </div>
    </div>

</div>
@endsection
