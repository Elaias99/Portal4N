@extends('layouts.app')

@section('content')
<div class="container">

    <h1 class="mb-4">Honorarios Mensuales Recibidos</h1>

    {{-- =========================
        IMPORTAR ARCHIVO SII
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            Importar archivo SII
        </div>
        <div class="card-body">
            <form action="{{ route('honorarios.mensual.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Archivo SII (file_informeMensualREC)</label>
                    <input type="file" name="archivo" class="form-control" required>
                </div>
                <button class="btn btn-primary">Importar y previsualizar</button>
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
                    {{ \Carbon\Carbon::create()->month($preview['meta']['mes'])->translatedFormat('F') }}
                    {{ $preview['meta']['anio'] }}

                </p>

                {{-- Tabla registros --}}
                <table class="table table-sm table-bordered mt-3">
                    <thead class="table-secondary">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Fecha Anulación</th>
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
                                <td>{{ $r['fecha_anulacion'] }}</td>
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
                            <td colspan="7">Totales</td>
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
        TARJETAS POR PERÍODO
    ========================== --}}
    <div id="vista-tarjetas">
        <div class="row">

            @forelse($periodos as $p)
                <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
                    <div class="card shadow-sm text-center h-100" style="border-radius: 12px;">

                        <div class="card-body d-flex flex-column justify-content-center">

                            {{-- Logo empresa --}}
                            <div style="
                                width: 120px;
                                height: 60px;
                                margin: 0 auto;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                @if($p->empresa && $p->empresa->logo)
                                    <img src="{{ url($p->empresa->logo) }}"
                                        alt="Logo {{ $p->empresa->Nombre }}"
                                        style="
                                            max-width: 100%;
                                            max-height: 100%;
                                            object-fit: contain;
                                        ">
                                @endif
                            </div>


                            {{-- Periodo --}}
                            <span class="text-muted">
                                {{ \Carbon\Carbon::create()->month($p->mes)->translatedFormat('F') }}
                                {{ $p->anio }}
                            </span>

                            {{-- Acción (placeholder) --}}
                            <div class="mt-3">



                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm btn-ver-detalle"
                                    data-empresa="{{ $p->empresa_id }}"
                                    data-anio="{{ $p->anio }}"
                                    data-mes="{{ $p->mes }}"
                                >
                                    Ver detalles
                                </button>





                            </div>

                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted">No hay honorarios importados.</p>
            @endforelse

        </div>
    </div>

    <div id="vista-detalle" style="display: none;">

        <div id="contenedor-detalle">
            
        </div>

    </div>


    <div class="text-center mt-4">
        <a href="{{ route('boleta.mensual.panel') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <- Ir a Panel Boletas honorarios
        </a>
    </div>


</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.addEventListener('click', function (e) {

        if (e.target && e.target.id === 'btn-volver-tarjetas') {

            document.getElementById('vista-detalle').style.display = 'none';
            document.getElementById('vista-tarjetas').style.display = 'block';
        }

    });




    const botonesDetalle = document.querySelectorAll('.btn-ver-detalle');

    botonesDetalle.forEach(function (btn) {
        btn.addEventListener('click', function () {

            const empresa = btn.dataset.empresa;
            const anio    = btn.dataset.anio;
            const mes     = btn.dataset.mes;

            // Ocultar tarjetas
            document.getElementById('vista-tarjetas').style.display = 'none';

            // Mostrar detalle
            document.getElementById('vista-detalle').style.display = 'block';

            // Cargar detalle real
            fetch(`/honorarios/mensual-rec/detalle/${empresa}/${anio}/${mes}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenedor-detalle').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('contenedor-detalle').innerHTML =
                        '<div class="alert alert-danger">Error al cargar el detalle.</div>';
                });
        });
    });








});
</script>


@endsection
