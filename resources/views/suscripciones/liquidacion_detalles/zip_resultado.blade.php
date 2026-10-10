@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1">ZIP de pre-facturas</h1>

            <div class="text-muted">
                Pre-facturas de
                <strong>{{ mb_strtoupper($mesNombre) }} {{ $anio }}</strong>
            </div>
        </div>

        @unless($continuar)
            <a href="{{ route('suscripciones.liquidacion-detalles.index', [
                'proveedor' => $proveedorFiltro,
                'rut' => $rutFiltro,
                'tipo' => $tipoFiltro,
                'anio' => $anio,
                'mes' => $mes,
            ]) }}"
               class="btn btn-secondary">
                Volver
            </a>
        @endunless
    </div>

    @if($continuar)
        {{-- Tanda intermedia: continúa sola con la siguiente. --}}
        <div class="alert alert-info">
            <strong>Preparando las pre-facturas…</strong>
            Van {{ $listos }} de {{ $total }}.
            No cierres ni recargues esta pestaña: la siguiente tanda parte sola
            y al final se descarga el ZIP.
        </div>

        <form
            id="form-continuar-zip"
            method="POST"
            action="{{ route('suscripciones.liquidacion-detalles.pdf-masivo') }}"
        >
            @csrf
            <input type="hidden" name="anio_pdf" value="{{ $anio }}">
            <input type="hidden" name="mes_pdf" value="{{ $mes }}">
            <input type="hidden" name="proveedor_pdf" value="{{ $proveedorFiltro }}">
            <input type="hidden" name="rut_pdf" value="{{ $rutFiltro }}">
            <input type="hidden" name="tipo_pdf" value="{{ $tipoFiltro }}">

            <button type="submit" class="btn btn-outline-primary">
                Continuar
            </button>
        </form>

        <script>
            setTimeout(function () {
                document.getElementById('form-continuar-zip').submit();
            }, 800);
        </script>
    @else
        <div class="alert alert-success">
            <strong>ZIP listo:</strong>
            {{ $total }} pre-facturas. La descarga parte sola; si no, usa el botón.
        </div>

        @if($drive['estado'] === 'guardado')
            <div class="alert alert-success">
                <strong>Drive compartido:</strong> {{ $drive['mensaje'] }}
            </div>
        @elseif($drive['estado'] === 'error')
            <div class="alert alert-danger">
                <strong>Drive compartido: NO se guardó la copia.</strong>
                La descarga funciona igual. Motivo: {{ $drive['mensaje'] }}
            </div>
        @else
            <div class="alert alert-secondary">
                <strong>Drive compartido:</strong> {{ $drive['mensaje'] }}
            </div>
        @endif

        <a id="btn-descargar-zip" href="{{ $urlDescarga }}" class="btn btn-primary">
            Descargar {{ $nombreDescarga }}
        </a>

        <script>
            setTimeout(function () {
                window.location.href = @json($urlDescarga);
            }, 500);
        </script>
    @endif

</div>
@endsection
