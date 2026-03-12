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

                <div class="panel-operativo-card__actions d-flex gap-2 flex-wrap">
                    <span class="panel-soft-chip panel-soft-chip--warning">
                        {{ $programadosHoy->count() }} programado(s)
                    </span>

                    <button type="button"
                            class="btn btn-sm btn-success rounded-pill px-3"
                            id="btn-pagar-programados-hoy">
                        Registrar pago
                    </button>

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
                                <th class="text-center" style="width:40px;">
                                    <input type="checkbox" id="check-all-programados-hoy">
                                </th>
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
                                        <td class="text-center">
                                            <input type="checkbox"
                                                class="chk-programado-hoy"
                                                value="{{ $h->id }}"
                                                data-id="{{ $h->id }}"
                                                data-folio="{{ $h->folio }}"
                                                data-emisor="{{ $h->razon_social_emisor }}"
                                                data-saldo="{{ $h->saldo_pendiente ?? 0 }}">
                                        </td>
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

        <div class="modal fade" id="modalPagoProgramadosHoy" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST"
                    action="{{ route('honorarios.mensual.pago.masivo') }}"
                    id="form-pago-programados-hoy"
                    class="modal-content">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Registrar pago de programados para hoy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Honorarios seleccionados</label>
                            <div id="resumen-programados-hoy"
                                class="border rounded p-2"
                                style="min-height: 120px;">
                            </div>
                        </div>

                        <div id="inputs-programados-hoy"></div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Fecha de pago</label>
                            <input type="date"
                                name="fecha_pago"
                                class="form-control"
                                required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit"
                                class="btn btn-success">
                            Confirmar pago
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkAll = document.getElementById('check-all-programados-hoy');
            const checks = document.querySelectorAll('.chk-programado-hoy');
            const btnPagar = document.getElementById('btn-pagar-programados-hoy');
            const modalEl = document.getElementById('modalPagoProgramadosHoy');
            const resumenWrap = document.getElementById('resumen-programados-hoy');
            const inputsWrap = document.getElementById('inputs-programados-hoy');

            if (!checkAll || !btnPagar || !modalEl || !resumenWrap || !inputsWrap) {
                return;
            }

            const modal = new bootstrap.Modal(modalEl);

            checkAll.addEventListener('change', () => {
                checks.forEach(chk => {
                    chk.checked = checkAll.checked;
                });
            });

            checks.forEach(chk => {
                chk.addEventListener('change', () => {
                    if (!chk.checked) {
                        checkAll.checked = false;
                    }
                });
            });

            btnPagar.addEventListener('click', () => {
                const seleccionados = Array.from(document.querySelectorAll('.chk-programado-hoy:checked'));

                if (seleccionados.length === 0) {
                    alert('Debes seleccionar al menos un honorario.');
                    return;
                }

                resumenWrap.innerHTML = '';
                inputsWrap.innerHTML = '';

                seleccionados.forEach(chk => {
                    const card = document.createElement('div');
                    card.className = 'border rounded p-2 mb-2 bg-light';

                    card.innerHTML = `
                        <div><strong>Folio:</strong> ${chk.dataset.folio}</div>
                        <div><strong>Emisor:</strong> ${chk.dataset.emisor}</div>
                        <div><strong>Saldo:</strong> ${chk.dataset.saldo}</div>
                    `;

                    resumenWrap.appendChild(card);

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'honorarios[]';
                    input.value = chk.value;

                    inputsWrap.appendChild(input);
                });

                modal.show();
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                resumenWrap.innerHTML = '';
                inputsWrap.innerHTML = '';
            });
        });
        </script>
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
