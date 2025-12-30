@extends('layouts.app')

@section('content')
<div class="container">

    <h1>Resumen Honorarios (bte_indiv_cons4)</h1>

    {{-- =========================
        FORMULARIO IMPORTACIÓN
    ========================== --}}
    <form action="{{ route('honorarios.resumen.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="archivo" class="form-label">
                Archivo SII (bte_indiv_cons4)
            </label>
            <input type="file" name="archivo" id="archivo" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Importar
        </button>
    </form>

    {{-- =========================
        PREVISUALIZACIÓN
    ========================== --}}
    @if(isset($preview))
        <hr>

        <h3 class="mt-4">Previsualización archivo SII</h3>

        <p><strong>Contribuyente:</strong> {{ $preview['razon_social'] }}</p>
        <p><strong>RUT:</strong> {{ $preview['rut_contribuyente'] }}</p>
        <p>
            <strong>Periodo:</strong>
            {{ $preview['mes'] ? $preview['mes'].' / ' : '' }}{{ $preview['anio'] }}
        </p>

        {{-- TABLA DETALLE POR BOLETA --}}
        <table class="table table-sm table-bordered mt-3">
            <thead class="table-light">
                <tr>
                    <th>Folio</th>
                    <th>Estado</th>
                    <th>Fecha Boleta</th>

                    <th>Rut Emisor</th>
                    <th>Nombre Emisor</th>
                    <th>Fecha Emisión</th>

                    <th>Rut Receptor</th>
                    <th>Nombre Receptor</th>

                    <th class="text-end">Bruto</th>
                    <th class="text-end">Retenido</th>
                    <th class="text-end">Pagado</th>
                </tr>
            </thead>

            <tbody>
                @foreach($preview['registros'] as $r)
                    <tr>
                        <td>{{ $r['folio'] }}</td>
                        <td>{{ $r['estado'] }}</td>

                        <td>
                            {{ \Carbon\Carbon::parse($r['fecha_boleta'])->format('d-m-Y') }}
                        </td>

                        <td>{{ $r['rut_emisor'] }}</td>
                        <td>{{ $r['nombre_emisor'] }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($r['fecha_emision'])->format('d-m-Y') }}
                        </td>

                        <td>{{ $r['rut_receptor'] }}</td>
                        <td>{{ $r['nombre_receptor'] }}</td>

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

                {{-- FILA TOTALES --}}
                @if(isset($preview['totales']))
                    <tr class="table-secondary fw-bold">
                        <td colspan="8">Totales</td>
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
                @endif
            </tbody>
        </table>

        <small class="text-muted">
            (*) Los valores totales no consideran los montos de las boletas anuladas.
        </small>

        {{-- =========================
            CONFIRMAR IMPORTACIÓN
        ========================== --}}
        <form action="{{ route('honorarios.resumen.store') }}" method="POST" class="mt-3">
            @csrf
            <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">
            <button type="submit" class="btn btn-success">
                Confirmar importación
            </button>
        </form>
    @endif

</div>
@endsection
