@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Resumen Anual de Honorarios</h1>

    <form action="{{ route('honorarios.resumen.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="archivo" class="form-label">Archivo SII (bte_indiv_cons4)</label>
            <input type="file" name="archivo" id="archivo" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Importar
        </button>

        <a href="{{ route('honorarios.resumen.index') }}" class="btn btn-secondary">
            Volver
        </a>
    </form>

    @if(isset($preview))
        <h3 class="mt-4">Preview archivo SII</h3>

        <p><strong>Contribuyente:</strong> {{ $preview['razon_social'] }}</p>
        <p><strong>RUT:</strong> {{ $preview['rut_contribuyente'] }}</p>
        <p><strong>Año:</strong> {{ $preview['anio'] }}</p>

        <table class="table table-sm table-bordered mt-3">
            <thead class="table-light">
                <tr>
                    <th>Periodo</th>
                    <th>Folio Inicial</th>
                    <th>Folio Final</th>
                    <th>Vigentes</th>
                    <th>Nulas</th>
                    <th>Bruto</th>
                    <th>Retenciones</th>
                    <th>Líquido</th>
                </tr>
            </thead>

            <tbody>
                {{-- Filas por mes --}}
                @foreach($preview['meses'] as $m)
                    <tr>
                        <td>{{ $m['mes_nombre'] }}</td>
                        <td class="text-end">{{ $m['folio_inicial'] }}</td>
                        <td class="text-end">{{ $m['folio_final'] }}</td>
                        <td class="text-end">{{ $m['boletas_vigentes'] }}</td>
                        <td class="text-end">{{ $m['boletas_nulas'] }}</td>
                        <td class="text-end">{{ number_format($m['honorario_bruto'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($m['retenciones'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($m['total_liquido'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Fila de Totales --}}
                @if(isset($preview['totales']))
                    <tr class="table-secondary fw-bold">
                        <td>*Totales</td>
                        <td class="text-end">{{ $preview['totales']['folio_inicial'] }}</td>
                        <td class="text-end">{{ $preview['totales']['folio_final'] }}</td>
                        <td class="text-end">{{ $preview['totales']['boletas_vigentes'] }}</td>
                        <td class="text-end">{{ $preview['totales']['boletas_nulas'] }}</td>
                        <td class="text-end">{{ number_format($preview['totales']['honorario_bruto'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($preview['totales']['retenciones'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($preview['totales']['total_liquido'], 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <small class="text-muted">
            (*) Los valores totales no consideran los montos de las boletas anuladas.
        </small>

        @if(isset($preview))
            <form action="{{ route('honorarios.resumen.store') }}" method="POST" class="mt-3">
                @csrf

                {{-- Payload oculto --}}
                <input type="hidden" name="data" value="{{ base64_encode(json_encode($preview)) }}">

                <button type="submit" class="btn btn-success">
                    Confirmar importación
                </button>
            </form>
        @endif


    @endif
</div>
@endsection
