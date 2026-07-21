@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1">Revisión de destinatarios</h1>

            <div class="text-muted">
                Pre-facturas de
                <strong>{{ mb_strtoupper($mesNombre) }} {{ $anio }}</strong>
            </div>
        </div>

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
    </div>

    <div class="alert alert-warning">
        <strong>Revisión previa:</strong>
        esta pantalla no envía correos. Solamente muestra los destinatarios
        que serían utilizados en un envío real.
    </div>

    <div class="row g-3 mb-4">

        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">
                        Proveedores
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ $revision['total_proveedores'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">
                        Pre-facturas PDF
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ $revision['total_prefacturas'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card h-100 border-success">
                <div class="card-body text-center">
                    <div class="small text-muted">
                        Listos para enviar
                    </div>

                    <div class="fs-4 fw-bold text-success">
                        {{ $revision['listos'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card h-100 border-danger">
                <div class="card-body text-center">
                    <div class="small text-muted">
                        Con problemas
                    </div>

                    <div class="fs-4 fw-bold text-danger">
                        {{
                            $revision['sin_correo']
                            + $revision['correos_invalidos']
                        }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Proveedores y correos registrados</strong>

            <span class="small text-muted">
                Sin correo: {{ $revision['sin_correo'] }}
                |
                Inválidos: {{ $revision['correos_invalidos'] }}
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Correo</th>
                            <th class="text-center">PDF</th>
                            <th>Grupo(s)</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($revision['proveedores'] as $destinatario)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $destinatario['proveedor'] }}
                                    </strong>
                                </td>

                                <td class="text-nowrap">
                                    {{ $destinatario['rut'] }}
                                </td>

                                <td>
                                    @if($destinatario['correo'] !== '')
                                        {{ $destinatario['correo'] }}
                                    @else
                                        <span class="text-muted">
                                            No registrado
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ $destinatario['cantidad_pdfs'] }}
                                </td>

                                <td>
                                    @forelse($destinatario['grupos'] as $grupo)
                                        <span class="badge bg-secondary me-1">
                                            {{ $grupo }}
                                        </span>
                                    @empty
                                        <span class="text-muted">
                                            GENERAL
                                        </span>
                                    @endforelse
                                </td>

                                <td class="text-center">
                                    @if($destinatario['estado'] === 'listo')
                                        <span class="badge bg-success">
                                            Listo para enviar
                                        </span>
                                    @elseif($destinatario['estado'] === 'sin_correo')
                                        <span class="badge bg-danger">
                                            Sin correo
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            Correo inválido
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 text-muted small">
        Ningún correo ha sido enviado desde esta pantalla.
    </div>

</div>
@endsection