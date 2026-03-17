@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')

<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="panel-finanzas-header text-center mb-5">
        <span class="panel-finanzas-header__eyebrow">Área de Finanzas</span>
        <h2 class="fw-bold mb-2">Módulo Finanzas</h2>
        <p class="text-muted mb-0">
            Panel central de gestión — Documentos, abonos, cruces y movimientos
        </p>
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

                    <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                            id="btn-eliminar-compras-programadas-hoy">
                        Quitar programación
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
                                                data-programado-id="{{ $programado->id }}"
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
    @endif

    @if($comprasProgramadasAtrasadas->isNotEmpty())
        <section class="panel-operativo-card panel-operativo-card--danger mb-4">
            <div class="panel-operativo-card__head">
                <div>
                    <div class="panel-operativo-card__kicker panel-operativo-card__kicker--danger">
                        Atención operativa
                    </div>

                    <h5 class="panel-operativo-card__title mb-1">
                        Compras con pago programado atrasado
                    </h5>

                    <p class="panel-operativo-card__text mb-0">
                        Hay <strong>{{ $comprasProgramadasAtrasadas->count() }}</strong> documento(s) con fecha programada vencida.
                    </p>
                </div>

                <div class="panel-operativo-card__actions d-flex gap-2 flex-wrap">
                    <span class="panel-soft-chip panel-soft-chip--danger">
                        {{ $comprasProgramadasAtrasadas->count() }} pendiente(s)
                    </span>

                    <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                            id="btn-eliminar-compras-programadas-atrasadas">
                        Quitar programación
                    </button>

                    {{-- <a href="{{ route('finanzas_compras.index') }}"
                    class="btn btn-sm btn-outline-danger rounded-pill px-3">
                        Revisar pendientes
                    </a> --}}
                </div>
            </div>

            <div class="panel-operativo-card__body mt-3">
                <div class="table-responsive">
                    <table class="table table-finanzas panel-programados-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:40px;">
                                    <input type="checkbox" id="check-all-compras-programadas-atrasadas">
                                </th>
                                <th>Empresa</th>
                                <th>Proveedor</th>
                                <th>Folio</th>
                                <th>Fecha programada</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comprasProgramadasAtrasadas as $programado)
                                @php $d = $programado->documentoCompra; @endphp
                                @if($d)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox"
                                                class="chk-compra-programada-atrasada"
                                                value="{{ $programado->id }}"
                                                data-programado-id="{{ $programado->id }}"
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
                                            <span class="panel-soft-chip panel-soft-chip--danger-date">
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
    @endif


    <form method="POST"
        action="{{ route('finanzas_compras.pago-programado.destroy.masivo') }}"
        id="form-eliminar-programados-compras">
        @csrf
        @method('DELETE')

        <div id="inputs-eliminar-programados-compras"></div>
    </form>

    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <section class="panel-accesos-directos mb-4">
        <div class="row justify-content-center g-4">

            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ route('cobranzas.documentos') }}"
                   class="panel-link-card panel-link-card--secondary">
                    <div class="panel-link-card__top">
                        <span class="panel-soft-chip panel-soft-chip--neutral">Cobranza activa</span>
                        <span class="panel-link-card__hint">Entrar</span>
                    </div>

                    <div class="panel-link-card__body">
                        <h5 class="panel-link-card__title">Cuentas por Cobrar</h5>
                        <p class="panel-link-card__text">
                            Gestión de facturas, notas de crédito y seguimiento operativo de cobranza.
                        </p>
                    </div>

                    <div class="panel-link-card__footer">
                        <span class="panel-link-card__cta">Abrir módulo</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ route('finanzas_compras.index') }}"
                   class="panel-link-card panel-link-card--primary">
                    <div class="panel-link-card__top">
                        <span class="panel-soft-chip panel-soft-chip--date">Operación compras</span>
                        <span class="panel-link-card__hint">Entrar</span>
                    </div>

                    <div class="panel-link-card__body">
                        <h5 class="panel-link-card__title">Cuentas por Pagar</h5>
                        <p class="panel-link-card__text">
                            Gestión de facturas de compras, pagos, referencias y control de proveedores.
                        </p>
                    </div>

                    <div class="panel-link-card__footer">
                        <span class="panel-link-card__cta">Abrir módulo</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ route('boleta.mensual.panel') }}"
                   class="panel-link-card panel-link-card--secondary">
                    <div class="panel-link-card__top">
                        <span class="panel-soft-chip panel-soft-chip--neutral">Operación honorarios</span>
                        <span class="panel-link-card__hint">Entrar</span>
                    </div>

                    <div class="panel-link-card__body">
                        <h5 class="panel-link-card__title">Honorarios por Pagar</h5>
                        <p class="panel-link-card__text">
                            Revisión de boletas de honorarios, próximos pagos, movimientos y operación mensual.
                        </p>
                    </div>

                    <div class="panel-link-card__footer">
                        <span class="panel-link-card__cta">Abrir módulo</span>
                    </div>
                </a>
            </div>

        </div>
    </section>

    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>








<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkAllHoy = document.getElementById('check-all-compras-programadas-hoy');
        const checksHoy = document.querySelectorAll('.chk-compra-programada-hoy');
        const btnPagarHoy = document.getElementById('btn-pagar-compras-programadas-hoy');
        const btnEliminarHoy = document.getElementById('btn-eliminar-compras-programadas-hoy');

        const modalEl = document.getElementById('modalPagoComprasProgramadasHoy');
        const resumenWrap = document.getElementById('resumen-compras-programadas-hoy');
        const inputsWrap = document.getElementById('inputs-compras-programadas-hoy');

        const checkAllAtrasadas = document.getElementById('check-all-compras-programadas-atrasadas');
        const checksAtrasadas = document.querySelectorAll('.chk-compra-programada-atrasada');
        const btnEliminarAtrasadas = document.getElementById('btn-eliminar-compras-programadas-atrasadas');

        const formEliminar = document.getElementById('form-eliminar-programados-compras');
        const inputsEliminar = document.getElementById('inputs-eliminar-programados-compras');

        let modal = null;

        if (modalEl) {
            modal = new bootstrap.Modal(modalEl);
        }

        if (checkAllHoy) {
            checkAllHoy.addEventListener('change', () => {
                checksHoy.forEach(chk => {
                    chk.checked = checkAllHoy.checked;
                });
            });

            checksHoy.forEach(chk => {
                chk.addEventListener('change', () => {
                    if (!chk.checked) {
                        checkAllHoy.checked = false;
                    }
                });
            });
        }

        if (checkAllAtrasadas) {
            checkAllAtrasadas.addEventListener('change', () => {
                checksAtrasadas.forEach(chk => {
                    chk.checked = checkAllAtrasadas.checked;
                });
            });

            checksAtrasadas.forEach(chk => {
                chk.addEventListener('change', () => {
                    if (!chk.checked) {
                        checkAllAtrasadas.checked = false;
                    }
                });
            });
        }

        if (btnPagarHoy && modalEl && resumenWrap && inputsWrap && modal) {
            btnPagarHoy.addEventListener('click', () => {
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
        }

        function eliminarProgramados(selector, mensajeConfirmacion) {
            if (!formEliminar || !inputsEliminar) {
                return;
            }

            const seleccionados = Array.from(document.querySelectorAll(`${selector}:checked`));

            if (seleccionados.length === 0) {
                alert('Debes seleccionar al menos un registro programado.');
                return;
            }

            if (!confirm(mensajeConfirmacion)) {
                return;
            }

            inputsEliminar.innerHTML = '';

            const ids = [...new Set(
                seleccionados
                    .map(chk => chk.dataset.programadoId)
                    .filter(id => id)
            )];

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'programados[]';
                input.value = id;
                inputsEliminar.appendChild(input);
            });

            formEliminar.submit();
        }

        if (btnEliminarHoy) {
            btnEliminarHoy.addEventListener('click', () => {
                eliminarProgramados(
                    '.chk-compra-programada-hoy',
                    '¿Deseas quitar la fecha de próximo pago de los documentos seleccionados?'
                );
            });
        }

        if (btnEliminarAtrasadas) {
            btnEliminarAtrasadas.addEventListener('click', () => {
                eliminarProgramados(
                    '.chk-compra-programada-atrasada',
                    '¿Deseas quitar la fecha de próximo pago de los documentos atrasados seleccionados?'
                );
            });
        }
    });
</script>
@endsection