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
        <p><strong>Año:</strong> {{ $preview['anio'] }}</p>

        {{-- TABLA RESUMEN MENSUAL --}}
        <table class="table table-sm table-bordered mt-3">
            <thead class="table-light">
                <tr>
                    <th>Mes</th>
                    <th class="text-end">Folio Inicial</th>
                    <th class="text-end">Folio Final</th>
                    <th class="text-end">Vigentes</th>
                    <th class="text-end">Nulas</th>
                    <th class="text-end">Honorario Bruto</th>
                    <th class="text-end">Retenciones</th>
                    <th class="text-end">Total Líquido</th>
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
                        <td class="text-end">
                            {{ number_format($r['honorario_bruto'], 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($r['retenciones'], 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($r['total_liquido'], 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach

                {{-- FILA TOTALES --}}
                @if(isset($preview['totales']))
                    <tr class="table-secondary fw-bold">
                        <td colspan="5">Totales</td>
                        <td class="text-end">
                            {{ number_format($preview['totales']['bruto'], 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($preview['totales']['retenido'], 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($preview['totales']['liquido'], 0, ',', '.') }}
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
