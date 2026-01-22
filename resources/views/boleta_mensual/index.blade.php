@extends('layouts.app')

@section('content')
<div class="container">


    

    <h1 class="mb-4">Honorarios Mensuales Recibidos</h1>



    {{-- =========================
    FILTROS + IMPORTACIÓN
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Filtros y carga de archivo SII</strong>
        </div>

        <div class="card-body">

            {{-- =========================
                FILTROS
            ========================== --}}
            <form method="GET" action="{{ route('honorarios.mensual.index') }}">
                <div class="row g-2 align-items-end">

                    {{-- Empresa --}}
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <select name="empresa_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}"
                                    {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Año --}}
                    <div class="col-md-2">
                        <label class="form-label">Año</label>
                        <select name="anio" class="form-select">
                            <option value="">Todos</option>
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}"
                                    {{ request('anio') == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Mes --}}
                    <div class="col-md-3">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-select">
                            <option value="">Todos</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}"
                                    {{ request('mes') == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Botones filtros --}}
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('honorarios.mensual.index') }}"
                        class="btn btn-outline-secondary">
                            Limpiar
                        </a>
                    </div>

                </div>
            </form>

            <hr>

            {{-- =========================
                IMPORTAR ARCHIVO SII
            ========================== --}}
            <form action="{{ route('honorarios.mensual.import') }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-2">

                @csrf

                <div class="row g-2 align-items-end">

                    <div class="col-md-9">
                        <label class="form-label">
                            Archivo SII (file_informeMensualREC)
                        </label>
                        <input type="file"
                            name="archivo"
                            class="form-control"
                            required>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-success w-100">
                            Importar y previsualizar
                        </button>
                    </div>

                </div>
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


        @php
            $preview = session('preview');
        @endphp


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
    REPORTE HONORARIOS MENSUALES
    ========================== --}}
    <div class="card mt-4">

        <div class="card-header">
            <strong>Reporte Honorarios Mensuales</strong>
        </div>

        <div class="card-body p-0">

            @if($registros->isEmpty())
                <div class="p-3">
                    <p class="text-muted mb-0">No hay honorarios registrados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">

                        <thead>
                            <tr>

                                <th>Empresa</th>

                                <th>Estado</th>

                                <th>Rut</th>
                                <th>Emisor</th>
                                <th>Folio</th>
                                <th>Fecha Emisión</th>
                               
                                <th>Estado SII</th>
                                <th>Fecha Anulación</th>
                                <th>Monto Pagado</th>
                                <th>Saldo pendiente</th>

                                <th>Fecha Cambio Estado</th>

                                
                            </tr>
                        </thead>


                        <tbody>
                        @foreach($registros as $r)
                            <tr>

                                <td>{{ $r->empresa->Nombre }}</td>


                                {{-- Estado financiero inicial --}}
                                <td>
                                    @php
                                        $estadoActual = $r->estado_financiero ?? $r->estado_financiero_inicial;
                                    @endphp

                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary btn-estado-honorario"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEstadoHonorario"
                                            data-id="{{ $r->id }}"
                                            data-emisor="{{ $r->razon_social_emisor }}"
                                            data-estado="{{ $r->estado_financiero ?? $r->estado_financiero_inicial }}"
                                            data-saldo="{{ number_format($r->saldo_pendiente ?? 0, 0, ',', '.') }}">
                                        {{ $r->estado_financiero ?? $r->estado_financiero_inicial }}
                                    </button>

                                </td>

                                <td>

                                    {{ $r->rut_emisor }}

                                </td>

                                {{-- Cobranza --}}
                                <td> @if ($r->cobranzaCompra) {{ $r->cobranzaCompra->razon_social }} @else <span class="text-muted">Sin proveedor</span> @endif </td>

                                <td> {{ $r->folio }} </td>

                                <td> {{ $r->fecha_emision }} </td>



                                <td>
                                    <span class="{{ $r->estado === 'ANULADA' ? 'text-danger' : 'text-success' }}">
                                        {{ $r->estado }}
                                    </span>
                                </td>

                                <td> {{ $r->fecha_anulacion }} </td>


                                {{-- Saldo pendiente --}}
                                <td class="text-end fw-bold">
                                    {{ number_format($r->monto_pagado ?? 0, 0, ',', '.') }}
                                </td>

                                {{-- Saldo pendiente --}}
                                <td class="text-end fw-bold">
                                    {{ number_format($r->saldo_pendiente ?? 0, 0, ',', '.') }}
                                </td>

                                <td> {{ $r->fecha_estado_financiero }} </td>
                                





                            </tr>
                         @endforeach
                        </tbody>


                    </table>


                    {{-- PAGINACIÓN --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $registros->links('pagination::bootstrap-4') }}
                    </div>

                </div>
            @endif

        </div>

    </div>








    <div class="text-center mt-4">
        <a href="{{ route('boleta.mensual.panel') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <- Ir a Panel Boletas honorarios
        </a>
    </div>


</div>







<script>
    document.addEventListener('click', function (e) {

        const btn = e.target.closest('.btn-estado-honorario');
        if (!btn) return;

        document.getElementById('modal-emisor').value         = btn.dataset.emisor;
        document.getElementById('modal-estado-actual').value = btn.dataset.estado;
        document.getElementById('modal-saldo').value          = btn.dataset.saldo;
        document.getElementById('modal-honorario-id').value   = btn.dataset.id;

        // Reset selector
        document.getElementById('modal-nuevo-estado').value = '';

        // Ocultar TODOS los bloques
        document.getElementById('modal-campo-abono').classList.add('d-none');
        document.getElementById('modal-campo-cruce').classList.add('d-none');
        document.getElementById('modal-campo-pago').classList.add('d-none');
        document.getElementById('modal-campo-pronto-pago').classList.add('d-none');
    });
</script>




<script>
    document.addEventListener('change', function (e) {

        if (e.target.id !== 'modal-nuevo-estado') return;

        const estado = e.target.value;

        const campoAbono   = document.getElementById('modal-campo-abono');
        const campoCruce   = document.getElementById('modal-campo-cruce');
        const campoPago    = document.getElementById('modal-campo-pago');
        const campoPronto  = document.getElementById('modal-campo-pronto-pago');

        // Ocultar todo
        campoAbono.classList.add('d-none');
        campoCruce.classList.add('d-none');
        campoPago.classList.add('d-none');
        campoPronto.classList.add('d-none');

        // Mostrar según estado
        if (estado === 'Abono') {
            campoAbono.classList.remove('d-none');
        }

        if (estado === 'Cruce') {
            campoCruce.classList.remove('d-none');
        }

        if (estado === 'Pago') {
            campoPago.classList.remove('d-none');
        }

        if (estado === 'Pronto pago') {
            campoPronto.classList.remove('d-none');
        }
    });
</script>





@include('boleta_mensual._modal_estado_financiero')


@endsection
