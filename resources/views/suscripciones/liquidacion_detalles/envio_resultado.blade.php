@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1">Envío de pre-facturas</h1>

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

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Pre-facturas</div>
                    <div class="fs-4 fw-bold">{{ $resultado['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-4">
            <div class="card h-100 border-success">
                <div class="card-body text-center">
                    <div class="small text-muted">Enviadas</div>
                    <div class="fs-4 fw-bold text-success">{{ $resultado['enviadas'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 border-danger">
                <div class="card-body text-center">
                    <div class="small text-muted">No enviadas</div>
                    <div class="fs-4 fw-bold text-danger">{{ $resultado['total'] - $resultado['enviadas'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($continuar)
        {{-- Tanda intermedia: continúa sola con la siguiente. --}}
        <div class="alert alert-info">
            <strong>Enviando en tandas…</strong>
            Van {{ $resultado['enviadas'] }} de {{ $resultado['total'] }}.
            No cierres ni recargues esta pestaña: la siguiente tanda parte sola.
        </div>

        <form
            id="form-continuar-envio"
            method="POST"
            action="{{ route('suscripciones.liquidacion-detalles.enviar-correos-reales-masivo') }}"
        >
            @csrf
            <input type="hidden" name="anio_pdf" value="{{ $anio }}">
            <input type="hidden" name="mes_pdf" value="{{ $mes }}">
            <input type="hidden" name="proveedor_pdf" value="{{ $proveedorFiltro }}">
            <input type="hidden" name="rut_pdf" value="{{ $rutFiltro }}">
            <input type="hidden" name="tipo_pdf" value="{{ $tipoFiltro }}">
            <input type="hidden" name="confirmacion_envio" value="ENVIAR">
            <input type="hidden" name="inicio_envio" value="{{ $inicioEnvio }}">

            <button type="submit" class="btn btn-outline-primary">
                Continuar envío
            </button>
        </form>

        <script>
            setTimeout(function () {
                document.getElementById('form-continuar-envio').submit();
            }, 1500);
        </script>
    @elseif($resultado['no_enviadas']->isEmpty())
        <div class="alert alert-success">
            <strong>Listo.</strong>
            Todas las pre-facturas de {{ $mesNombre }} {{ $anio }} fueron enviadas.
        </div>
    @else
        <div class="alert alert-warning">
            <strong>Estas pre-facturas NO se enviaron.</strong>
            Las que ya se enviaron no aparecen aquí y nunca se vuelven a enviar.
        </div>

        <div class="card mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>RUT</th>
                                <th>Correo</th>
                                <th>Grupo</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($resultado['no_enviadas'] as $item)
                                <tr>
                                    <td><strong>{{ $item['proveedor'] }}</strong></td>
                                    <td class="text-nowrap">{{ $item['rut'] }}</td>
                                    <td>
                                        @if($item['correo'] !== '')
                                            {{ $item['correo'] }}
                                        @else
                                            <span class="text-muted">No registrado</span>
                                        @endif
                                    </td>
                                    <td>{{ $item['grupo'] }}</td>
                                    <td>{{ $item['motivo'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('suscripciones.liquidacion-detalles.enviar-correos-reales-masivo') }}"
            onsubmit="return confirm('¿Reenviar sólo a los {{ $resultado['no_enviadas']->count() }} proveedores de la lista?');"
        >
            @csrf
            <input type="hidden" name="anio_pdf" value="{{ $anio }}">
            <input type="hidden" name="mes_pdf" value="{{ $mes }}">
            <input type="hidden" name="proveedor_pdf" value="{{ $proveedorFiltro }}">
            <input type="hidden" name="rut_pdf" value="{{ $rutFiltro }}">
            <input type="hidden" name="tipo_pdf" value="{{ $tipoFiltro }}">
            <input type="hidden" name="confirmacion_envio" value="ENVIAR">

            <button type="submit" class="btn btn-danger">
                Reenviar las no enviadas
            </button>
        </form>

        <div class="mt-2 text-muted small">
            Si el motivo es "Proveedor sin correo registrado", primero hay que cargar su correo;
            si no, se volverá a omitir.
        </div>
    @endif

</div>
@endsection
