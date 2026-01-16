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

                @include('boleta_honorario.partials.tabla_resumen', [
                    'resumen' => $preview['resumen_mensual'],
                    'totales' => $preview['totales'] ?? null
                ])

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

                        @include('boleta_honorario.partials.tabla_resumen', [
                            'resumen' => $meses,
                            'totales' => \App\Models\HonorarioResumenAnualTotal::where('rut_contribuyente', $rut)
                                ->where('anio', $anio)
                                ->first()
                        ])

                    @endforeach
                @endforeach
            @endif

        </div>
    </div>

</div>
@endsection
