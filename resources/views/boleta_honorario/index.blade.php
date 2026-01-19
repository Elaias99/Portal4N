@extends('layouts.app')

@section('content')
<div class="container">

    <h1 class="mb-4">Resumen Honorarios (bte_indiv_cons4)</h1>

    {{-- =========================
        1️⃣ FORMULARIO IMPORTACIÓN
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            Importar archivo SII
        </div>
        <div class="card-body">
            <form action="{{ route('honorarios.resumen.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Archivo SII (bte_indiv_cons4)</label>
                    <input type="file" name="archivo" class="form-control" required>
                </div>

                <button class="btn btn-primary">Importar y previsualizar</button>
            </form>
        </div>
    </div>

    {{-- =========================
        2️⃣ PREVISUALIZACIÓN
    ========================== --}}
    @if(isset($preview))
        <div class="card mb-5 border-warning">
            <div class="card-header bg-warning">
                <strong>Previsualización del archivo (sin guardar)</strong>
            </div>
            <div class="card-body">

                <p><strong>Contribuyente:</strong> {{ $preview['razon_social'] }}</p>
                <p><strong>RUT:</strong> {{ $preview['rut_contribuyente'] }}</p>
                <p><strong>Año:</strong> {{ $preview['anio'] }}</p>

                {{-- TABLA PREVIEW --}}
                <table class="table table-sm table-bordered mt-3">
                    <thead class="table-light">
                        <tr>
                            <th>Mes</th>
                            <th class="text-end">Folio Inicial</th>
                            <th class="text-end">Folio Final</th>
                            <th class="text-end">Vigentes</th>
                            <th class="text-end">Nulas</th>
                            <th class="text-end">Bruto</th>
                            <th class="text-end">Retenciones</th>
                            <th class="text-end">Líquido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['resumen_mensual'] as $r)
                            <tr>
                                <td>{{ $r['mes_nombre'] }}</td>
                                <td class="text-end">{{ $r['folio_inicial'] }}</td>
                                <td class="text-end">{{ $r['folio_final'] }}</td>
                                <td class="text-end">{{ $r['boletas_vigentes'] }}</td>
                                <td class="text-end">{{ $r['boletas_nulas'] }}</td>
                                <td class="text-end">{{ $r['honorario_bruto'] }}</td>
                                <td class="text-end">{{ $r['retenciones'] }}</td>
                                <td class="text-end">{{ $r['total_liquido'] }}</td>
                            </tr>
                        @endforeach

                        @if(isset($preview['totales']))
                        <tr class="table-secondary fw-bold">
                            <td colspan="5">Totales</td>
                            <td class="text-end">{{ $preview['totales']['bruto'] }}</td>
                            <td class="text-end">{{ $preview['totales']['retenido'] }}</td>
                            <td class="text-end">{{ $preview['totales']['liquido'] }}</td>
                        </tr>
                        @endif

                    </tbody>
                </table>

                <form action="{{ route('honorarios.resumen.store') }}" method="POST" class="mt-3">
                    @csrf
                    <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
                    <button class="btn btn-success">Confirmar importación</button>
                </form>

            </div>
        </div>
    @endif

    {{-- =========================
        3️⃣ REGISTROS ALMACENADOS
    ========================== --}}
    <div class="card">
        <div class="card-header">
            Resúmenes almacenados
        </div>
        <div class="card-body">

            @if($registros->isEmpty())
                <p>No hay registros cargados.</p>
            @else
                @php
                    $agrupados = $registros->groupBy(['rut_contribuyente', 'anio']);
                @endphp

                @foreach($agrupados as $rut => $porAnio)
                    @foreach($porAnio as $anio => $meses)

                        <h5 class="mt-4">
                            {{ $meses->first()->razon_social }}
                            — {{ $rut }} — Año {{ $anio }}
                        </h5>

                        @php
                            $totales = \App\Models\HonorarioResumenAnualTotal::where('rut_contribuyente', $rut)
                                ->where('anio', $anio)
                                ->first();
                        @endphp

                        {{-- TABLA GUARDADOS --}}
                        <table class="table table-sm table-bordered mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Mes</th>
                                    <th class="text-end">Folio Inicial</th>
                                    <th class="text-end">Folio Final</th>
                                    <th class="text-end">Vigentes</th>
                                    <th class="text-end">Nulas</th>
                                    <th class="text-end">Bruto</th>
                                    <th class="text-end">Retenciones</th>
                                    <th class="text-end">Líquido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($meses as $r)
                                    <tr>
                                        <td>{{ $r->mes_nombre }}</td>
                                        <td class="text-end">{{ $r->folio_inicial }}</td>
                                        <td class="text-end">{{ $r->folio_final }}</td>
                                        <td class="text-end">{{ $r->boletas_vigentes }}</td>
                                        <td class="text-end">{{ $r->boletas_nulas }}</td>
                                        <td class="text-end">{{ $r->honorario_bruto }}</td>
                                        <td class="text-end">{{ $r->retenciones }}</td>
                                        <td class="text-end">{{ $r->total_liquido }}</td>
                                    </tr>
                                @endforeach

                                @if($totales)
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="5">Totales</td>
                                        <td class="text-end">{{ $totales->honorario_bruto }}</td>
                                        <td class="text-end">{{ $totales->retenciones }}</td>
                                        <td class="text-end">{{ $totales->total_liquido }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                    @endforeach
                @endforeach
            @endif

        </div>
    </div>



    <div class="text-center mt-4">
        <a href="{{ route('boleta.mensual.panel') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <- Ir a Panel Boletas honorarios
        </a>
    </div>

</div>
@endsection
