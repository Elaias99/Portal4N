@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')

<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold">Módulo Finanzas</h2>
        <p class="text-muted mb-0">Panel central de gestión — Documentos, abonos, cruces y movimientos</p>
    </div>



    @if($comprasProgramadasHoy->isNotEmpty())
        <section class="panel-operativo-card panel-operativo-card--warning mb-4">
            <div class="panel-operativo-card__head">
                <div>
                    <div class="panel-operativo-card__kicker">
                        Revisión del día
                    </div>

                    <h5 class="panel-operativo-card__title mb-1">
                        Compras con pago programado para hoy
                    </h5>

                    <p class="panel-operativo-card__text mb-0">
                        Hay <strong>{{ $comprasProgramadasHoy->count() }}</strong> documento(s) de compra con próximo pago definido para hoy.
                    </p>
                </div>

                <div class="panel-operativo-card__actions d-flex gap-2 flex-wrap">
                    <span class="panel-soft-chip panel-soft-chip--warning">
                        {{ $comprasProgramadasHoy->count() }} programado(s)
                    </span>

                    <button type="button"
                            class="btn btn-sm btn-success rounded-pill px-3"
                            id="btn-pagar-compras-programadas-hoy">
                        Registrar pago
                    </button>

                    <a href="{{ route('finanzas_compras.index') }}"
                    class="btn btn-sm btn-outline-warning rounded-pill px-3">
                        Revisar compras
                    </a>
                </div>
            </div>

            <div class="panel-operativo-card__body mt-3">
                <div class="table-responsive">
                    <table class="table table-finanzas panel-programados-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:40px;">
                                    <input type="checkbox" id="check-all-compras-programadas-hoy">
                                </th>
                                <th>Empresa</th>
                                <th>Proveedor</th>
                                <th>Folio</th>
                                <th>Fecha programada</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comprasProgramadasHoy as $programado)
                                @php $d = $programado->documentoCompra; @endphp
                                @if($d)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox"
                                                class="chk-compra-programada-hoy"
                                                value="{{ $d->id }}"
                                                data-id="{{ $d->id }}"
                                                data-folio="{{ $d->folio }}"
                                                data-proveedor="{{ $d->razon_social }}"
                                                data-rut="{{ $d->rut_proveedor }}"
                                                data-saldo="{{ $d->saldo_pendiente ?? 0 }}">
                                        </td>
                                        <td>{{ $d->empresa->Nombre ?? '-' }}</td>
                                        <td>{{ $d->razon_social }}</td>
                                        <td>
                                            <a href="{{ route('finanzas_compras.show', $d->id) }}"
                                            class="panel-table-link">
                                                {{ $d->folio }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="panel-soft-chip panel-soft-chip--date">
                                                {{ $programado->fecha_programada?->format('d-m-Y') }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            ${{ number_format($d->saldo_pendiente ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="modal fade" id="modalPagoComprasProgramadasHoy" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST"
                    action="{{ route('documentos.pagos.masivo.panel_hoy') }}"
                    id="form-pago-compras-programadas-hoy"
                    class="modal-content">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Registrar pago de compras programadas para hoy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Documentos seleccionados</label>
                            <div id="resumen-compras-programadas-hoy"
                                class="border rounded p-2"
                                style="min-height: 120px;">
                            </div>
                        </div>

                        <div id="inputs-compras-programadas-hoy"></div>

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
            const checkAll = document.getElementById('check-all-compras-programadas-hoy');
            const checks = document.querySelectorAll('.chk-compra-programada-hoy');
            const btnPagar = document.getElementById('btn-pagar-compras-programadas-hoy');
            const modalEl = document.getElementById('modalPagoComprasProgramadasHoy');
            const resumenWrap = document.getElementById('resumen-compras-programadas-hoy');
            const inputsWrap = document.getElementById('inputs-compras-programadas-hoy');

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
                const seleccionados = Array.from(document.querySelectorAll('.chk-compra-programada-hoy:checked'));

                if (seleccionados.length === 0) {
                    alert('Debes seleccionar al menos un documento.');
                    return;
                }

                resumenWrap.innerHTML = '';
                inputsWrap.innerHTML = '';

                seleccionados.forEach(chk => {
                    const card = document.createElement('div');
                    card.className = 'border rounded p-2 mb-2 bg-light';

                    card.innerHTML = `
                        <div><strong>Folio:</strong> ${chk.dataset.folio}</div>
                        <div><strong>Proveedor:</strong> ${chk.dataset.proveedor}</div>
                        <div><strong>RUT:</strong> ${chk.dataset.rut}</div>
                        <div><strong>Saldo:</strong> ${chk.dataset.saldo}</div>
                    `;

                    resumenWrap.appendChild(card);

                    const inputDocumento = document.createElement('input');
                    inputDocumento.type = 'hidden';
                    inputDocumento.name = 'documentos[]';
                    inputDocumento.value = chk.value;
                    inputsWrap.appendChild(inputDocumento);

                    const inputOperacion = document.createElement('input');
                    inputOperacion.type = 'hidden';
                    inputOperacion.name = `operaciones[${chk.value}]`;
                    inputOperacion.value = 'pago';
                    inputsWrap.appendChild(inputOperacion);
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

    @if($comprasProgramadasAtrasadas->isNotEmpty())
        <div class="alert alert-danger shadow-sm mb-4" style="border-left: 5px solid #dc3545; border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Compras con pago programado atrasado</h5>
                    <p class="mb-0">
                        Hay <strong>{{ $comprasProgramadasAtrasadas->count() }}</strong> documento(s) con fecha programada vencida.
                    </p>
                </div>

                <div>
                    <a href="{{ route('finanzas_compras.index') }}" class="btn btn-sm btn-outline-danger">
                        Revisar pendientes
                    </a>
                </div>
            </div>
        </div>
    @endif


    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <div class="row justify-content-center text-center g-4 mb-4">

        {{-- === Cuentas por Cobrar === --}}
        <div class="col-md-3">
            <a href="{{ route('cobranzas.documentos') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Cuentas por Cobrar</h6>
                        <p class="text-muted small mb-0">Gestión de facturas y notas de crédito</p>
                    </div>
                </div>
            </a>
        </div>




        {{-- === Cuentas por Pagar === --}}
        <div class="col-md-3">
            <a href="{{ route('finanzas_compras.index') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Cuentas por Pagar</h6>
                        <p class="text-muted small mb-0">Gestión de facturas de compras y proveedores</p>
                    </div>
                </div>
            </a>
        </div>



        <div class="col-md-3">
            <a href="{{ route('boleta.mensual.panel') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Honorarios por Pagar</h6>
                        <p class="text-muted small mb-0">Boleta honorarios</p>
                    </div>
                </div>
            </a>
        </div>



    </div>




    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>
@endsection
