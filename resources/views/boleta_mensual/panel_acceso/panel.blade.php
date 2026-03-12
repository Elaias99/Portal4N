@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')





<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="panel-finanzas-header text-center mb-5">
        <span class="panel-finanzas-header__eyebrow">Área de Finanzas</span>
        <h2 class="fw-bold mb-2">Módulo Boleta Honorarios</h2>
        <p class="text-muted mb-0">
            Panel central de gestión — Documentos, abonos, cruces y movimientos
        </p>
    </div>


    @if($programadosHoy->isNotEmpty())
        <section class="panel-operativo-card panel-operativo-card--warning mb-4">
            <div class="panel-operativo-card__head">
                <div>
                    <div class="panel-operativo-card__kicker">
                        Revisión del día
                    </div>

                    <h5 class="panel-operativo-card__title mb-1">
                        Pagos programados para hoy
                    </h5>

                    <p class="panel-operativo-card__text mb-0">
                        Hay <strong>{{ $programadosHoy->count() }}</strong> honorario(s) con próximo pago definido para hoy.
                        Revísalos antes de cerrar la jornada.
                    </p>
                </div>

                <div class="panel-operativo-card__actions">
                    <span class="panel-soft-chip panel-soft-chip--warning">
                        {{ $programadosHoy->count() }} programado(s)
                    </span>

                    <a href="{{ route('honorarios.mensual.index') }}"
                       class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        Revisar honorarios
                    </a>
                </div>
            </div>

            <div class="panel-operativo-card__body mt-3">
                <div class="table-responsive">
                    <table class="table table-finanzas panel-programados-table mb-0">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Emisor</th>
                                <th>Folio</th>
                                <th>Servicio</th>
                                <th>Fecha programada</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($programadosHoy as $programado)
                                @php $h = $programado->honorarioMensualRec; @endphp
                                @if($h)
                                    <tr>
                                        <td>{{ $h->empresa->Nombre ?? '-' }}</td>
                                        <td>{{ $h->razon_social_emisor }}</td>
                                        <td>
                                            <a href="{{ route('honorarios.mensual.show', $h->id) }}"
                                               class="panel-table-link">
                                                {{ $h->folio }}
                                            </a>
                                        </td>
                                        <td>{{ $h->servicio_final ?? '-' }}</td>
                                        <td>
                                            <span class="panel-soft-chip panel-soft-chip--date">
                                                {{ $programado->fecha_programada?->format('d-m-Y') }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            ${{ number_format($h->saldo_pendiente ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif




    @if($programadosAtrasados->isNotEmpty())
        <div class="alert alert-danger border-start border-4 border-danger shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Pagos programados pendientes de revisión</h5>
                    <p class="mb-2">
                        Hay <strong>{{ $programadosAtrasados->count() }}</strong> honorario(s) con fecha programada vencida
                        que aún no han sido revisados.
                    </p>
                </div>

                <div>
                    <a href="{{ route('honorarios.mensual.index') }}" class="btn btn-sm btn-outline-danger">
                        Revisar pendientes
                    </a>
                </div>
            </div>
        </div>
    @endif


    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <section class="panel-accesos-directos mb-4">
        {{-- <div class="text-center mb-4">
            <h5 class="fw-semibold mb-1">Accesos directos del módulo</h5>
            <p class="text-muted mb-0">
                Navega rápidamente entre la gestión mensual operativa y la revisión anual consolidada.
            </p>
        </div> --}}

        <div class="row justify-content-center g-4">

            <div class="col-12 col-md-6 col-xl-5">
                <a href="{{ route('honorarios.mensual.index') }}"
                class="panel-link-card panel-link-card--primary">
                    <div class="panel-link-card__top">
                        <span class="panel-soft-chip panel-soft-chip--date">Operación mensual</span>
                        <span class="panel-link-card__hint">Entrar</span>
                    </div>

                    <div class="panel-link-card__body">
                        <h5 class="panel-link-card__title">Honorario Mensual REC</h5>
                        <p class="panel-link-card__text">
                            Revisa documentos, próximos pagos, movimientos y acciones operativas del ciclo mensual.
                        </p>
                    </div>

                    <div class="panel-link-card__footer">
                        <span class="panel-link-card__cta">Abrir módulo</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-5">
                <a href="{{ route('honorarios.resumen.index') }}"
                class="panel-link-card panel-link-card--secondary">
                    <div class="panel-link-card__top">
                        <span class="panel-soft-chip panel-soft-chip--neutral">Vista consolidada</span>
                        <span class="panel-link-card__hint">Entrar</span>
                    </div>

                    <div class="panel-link-card__body">
                        <h5 class="panel-link-card__title">Honorario Resumen Anual</h5>
                        <p class="panel-link-card__text">
                            Consulta la visión acumulada anual, carga información y revisa el consolidado histórico.
                        </p>
                    </div>

                    <div class="panel-link-card__footer">
                        <span class="panel-link-card__cta">Abrir resumen</span>
                    </div>
                </a>
            </div>

        </div>
    </section>

    <div class="text-center mt-4">
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <- Ir a Panel General de Cobranza
        </a>
    </div>





    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>
@endsection
