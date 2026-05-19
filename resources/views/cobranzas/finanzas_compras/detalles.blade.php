@extends('layouts.app')

@section('content')
@php
    $esNotaCredito = (int) $documento->tipo_documento_id === 61;
@endphp

<div class="container mt-4" style="max-width: 1100px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary mb-0">
            Detalles del Documento de Compra
        </h2>

        @if(!$esNotaCredito && Auth::id() != 375)
            <button type="button"
                    class="btn btn-outline-secondary btn-sm mt-1 px-2 py-0"
                    data-toggle="modal"
                    data-target="#modalEstadoCompra-{{ $documento->id }}"
                    data-bs-toggle="modal"
                    data-bs-target="#modalEstadoCompra-{{ $documento->id }}">
                Editar
            </button>
            @include('cobranzas.finanzas_compras.modal_estado', ['doc' => $documento])
        @endif
    </div>

    {{-- Información general --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Información general</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Empresa:</strong> {{ $documento->empresa?->Nombre ?? 'Sin empresa' }}</p>
                    <p><strong>Proveedor:</strong> {{ $documento->razon_social }}</p>
                    <p><strong>RUT Proveedor:</strong> {{ $documento->rut_proveedor }}</p>
                    <p><strong>Tipo Documento:</strong> {{ $documento->tipoDocumento?->nombre ?? '-' }}</p>
                    <p><strong>Folio:</strong> {{ $documento->folio }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Monto Total:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>
                    <p><strong>Saldo Pendiente:</strong> ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}</p>
                    <p><strong>Estado Actual:</strong> {{ $documento->estado ?? $documento->status_original }}</p>
                    <p><strong>Fecha Documento:</strong> {{ $documento->fecha_docto ? \Carbon\Carbon::parse($documento->fecha_docto)->format('d-m-Y') : '-' }}</p>
                    <p><strong>Fecha Vencimiento:</strong> {{ $documento->fecha_vencimiento ? \Carbon\Carbon::parse($documento->fecha_vencimiento)->format('d-m-Y') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Bloque especial para Nota de Crédito --}}
    @if($esNotaCredito)
        <div class="card mb-4 shadow-sm border-info">
            <div class="card-header bg-light fw-bold text-info">
                Nota de Crédito Electrónica
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Este documento corresponde a una Nota de Crédito. Su función principal en este módulo es
                    mantener la referencia documental contra la factura relacionada.
                </p>

                <p class="mb-2">
                    Las acciones financieras como pagos, abonos, cruces o pronto pago deben gestionarse desde la
                    factura referenciada, no directamente desde la Nota de Crédito.
                </p>

                @if($documento->referencia)
                    <p class="mb-0">
                        <strong>Factura referenciada:</strong>
                        {{ $documento->referencia->tipoDocumento->nombre ?? 'Documento' }}
                        Folio <strong>{{ $documento->referencia->folio }}</strong>
                        — Monto: ${{ number_format($documento->referencia->monto_total, 0, ',', '.') }}
                    </p>
                @else
                    <p class="text-muted mb-0">
                        Esta Nota de Crédito aún no tiene una factura referenciada.
                    </p>
                @endif
            </div>
        </div>
    @endif






    {{-- Resumen del cálculo del saldo pendiente --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Resumen del cálculo del saldo pendiente</div>
        <div class="card-body">

            @php
                $saldoBase = (int) ($documento->monto_total ?? 0);
                $saldoAntesMovimientos = $saldoBase;

                $lineasAjustes = collect();

                /*
                * En compras, las notas referenciadas tienen regla especial:
                * - NC resta, pero evitando doble descuento si ya fue materializada como abono/pago por referencia.
                * - ND suma.
                */
                if (!$esNotaCredito && $documento->referenciados->isNotEmpty()) {
                    $totalNotasCredito = $documento->referenciados
                        ->where('tipo_documento_id', 61)
                        ->sum('monto_total');

                    $totalAbonosReferencia = $documento->abonos
                        ->where('origen', 'referencia_nc')
                        ->sum('monto');

                    $tienePagoReferencia = $documento->pagos
                        ->where('origen', 'referencia_nc')
                        ->isNotEmpty();

                    $pendienteDescontarPorNC = $tienePagoReferencia
                        ? 0
                        : max($totalNotasCredito - $totalAbonosReferencia, 0);

                    if ($pendienteDescontarPorNC > 0) {
                        $saldoAntesMovimientos -= $pendienteDescontarPorNC;

                        $lineasAjustes->push([
                            'texto' => 'Descuento pendiente por Notas de Crédito referenciadas',
                            'signo' => '-',
                            'monto' => $pendienteDescontarPorNC,
                        ]);
                    }

                    $notasDebito = $documento->referenciados
                        ->where('tipo_documento_id', 56);

                    foreach ($notasDebito as $notaDebito) {
                        $montoNotaDebito = (int) ($notaDebito->monto_total ?? 0);
                        $saldoAntesMovimientos += $montoNotaDebito;

                        $lineasAjustes->push([
                            'texto' => 'Aumento por Nota de Débito folio ' . $notaDebito->folio,
                            'signo' => '+',
                            'monto' => $montoNotaDebito,
                        ]);
                    }
                }

                $movimientosResumen = collect();

                if (!$esNotaCredito) {
                    foreach ($documento->abonos as $abono) {
                        $movimientosResumen->push([
                            'tipo' => $abono->origen === 'referencia_nc'
                                ? 'Abono por referencia'
                                : 'Abono',
                            'fecha' => $abono->fecha_abono,
                            'fecha_orden' => $abono->fecha_abono,
                            'created_at_orden' => $abono->created_at,
                            'monto' => (int) ($abono->monto ?? 0),
                            'prioridad' => 10,
                            'registro' => $abono,
                            'origen' => $abono->origen,
                        ]);
                    }

                    foreach ($documento->cruces as $cruce) {
                        $movimientosResumen->push([
                            'tipo' => 'Cruce',
                            'fecha' => $cruce->fecha_cruce,
                            'fecha_orden' => $cruce->fecha_cruce,
                            'created_at_orden' => $cruce->created_at,
                            'monto' => (int) ($cruce->monto ?? 0),
                            'prioridad' => 20,
                            'registro' => $cruce,
                            'origen' => null,
                        ]);
                    }

                    foreach ($documento->pagos as $pago) {
                        $movimientosResumen->push([
                            'tipo' => $pago->origen === 'referencia_nc'
                                ? 'Pago por referencia'
                                : 'Pago',
                            'fecha' => $pago->fecha_pago,
                            'fecha_orden' => $pago->fecha_pago,
                            'created_at_orden' => $pago->created_at,
                            'monto' => null,
                            'prioridad' => 30,
                            'registro' => $pago,
                            'origen' => $pago->origen,
                        ]);
                    }

                    foreach ($documento->prontoPagos as $prontoPago) {
                        $movimientosResumen->push([
                            'tipo' => 'Pronto pago',
                            'fecha' => $prontoPago->fecha_pronto_pago,
                            'fecha_orden' => $prontoPago->fecha_pronto_pago,
                            'created_at_orden' => $prontoPago->created_at,
                            'monto' => null,
                            'prioridad' => 40,
                            'registro' => $prontoPago,
                            'origen' => null,
                        ]);
                    }
                }

                $movimientosResumen = $movimientosResumen
                    ->sortBy(function ($item) {
                        $fecha = $item['fecha_orden']
                            ? \Carbon\Carbon::parse($item['fecha_orden'])->format('Y-m-d')
                            : '9999-12-31';

                        $createdAt = $item['created_at_orden']
                            ? \Carbon\Carbon::parse($item['created_at_orden'])->format('H:i:s')
                            : '00:00:00';

                        return $fecha . ' ' . $createdAt . ' ' . str_pad($item['prioridad'], 2, '0', STR_PAD_LEFT);
                    })
                    ->values();

                $saldoCalculado = max($saldoAntesMovimientos, 0);
            @endphp

            <p class="mb-1">
                <strong>Monto total inicial:</strong>
                ${{ number_format($documento->monto_total, 0, ',', '.') }}
            </p>

            @if(!$esNotaCredito)

                {{-- Ajustes por referencias --}}
                @foreach($lineasAjustes as $lineaAjuste)
                    <p class="mb-1">
                        <strong>{{ $lineaAjuste['texto'] }}:</strong>
                        {{ $lineaAjuste['signo'] }} ${{ number_format($lineaAjuste['monto'], 0, ',', '.') }}
                    </p>
                @endforeach

                {{-- Estados / movimientos ordenados --}}
                @foreach($movimientosResumen as $movimiento)
                    @php
                        if ($movimiento['monto'] === null) {
                            $montoMovimiento = max($saldoCalculado, 0);
                        } else {
                            $montoMovimiento = (int) $movimiento['monto'];
                        }

                        $montoMovimiento = min($montoMovimiento, max($saldoCalculado, 0));
                        $saldoCalculado = max($saldoCalculado - $montoMovimiento, 0);

                        $fechaMovimiento = $movimiento['fecha']
                            ? \Carbon\Carbon::parse($movimiento['fecha'])->format('d-m-Y')
                            : '-';

                        $registro = $movimiento['registro'];
                    @endphp

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <p class="mb-0">
                            <strong>{{ $movimiento['tipo'] }} registrado el {{ $fechaMovimiento }}:</strong>
                            - ${{ number_format($montoMovimiento, 0, ',', '.') }}
                        </p>

                        @if($movimiento['tipo'] === 'Pago por referencia')
                            <span class="text-muted small">
                                Generado por referencia. Para revertirlo, quite la referencia del documento.
                            </span>
                        @elseif($movimiento['tipo'] === 'Pago')
                            <form action="{{ route('pagos.destroy', $registro->id) }}"
                                method="POST"
                                class="js-confirm-submit"
                                data-no-loader
                                data-confirm-title="Eliminar pago"
                                data-confirm-message="¿Seguro que deseas eliminar este Pago y restaurar el estado original del documento?"
                                data-confirm-button="Eliminar Pago">
                                @csrf
                                @method('DELETE')

                                @if (Auth::id() != 375)
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle"></i> Eliminar Pago
                                    </button>
                                @endif
                            </form>
                        @elseif($movimiento['tipo'] === 'Pronto pago')
                            <form action="{{ route('prontopagos.destroy', $registro->id) }}"
                                method="POST"
                                class="js-confirm-submit"
                                data-no-loader
                                data-confirm-title="Eliminar pronto pago"
                                data-confirm-message="¿Seguro que deseas eliminar el registro de Pronto Pago y restaurar el estado original del documento?"
                                data-confirm-button="Eliminar Pronto Pago">
                                @csrf
                                @method('DELETE')

                                @if (Auth::id() != 375)
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle"></i> Eliminar Pronto Pago
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>
                @endforeach

            @else

                <p class="text-muted mb-1 mt-3">
                    La Nota de Crédito se mantiene como documento de ajuste. Si está referenciada, su saldo queda en 0.
                </p>

                @if($documento->pagos()->exists())
                    @php
                        $pagoNc = $documento->pagos()->latest('fecha_pago')->first();
                    @endphp

                    <p class="text-muted mb-1">
                        <strong>Cierre registrado:</strong>
                        {{ $pagoNc->fecha_pago ? \Carbon\Carbon::parse($pagoNc->fecha_pago)->format('d-m-Y') : '-' }}
                        @if($pagoNc->origen)
                            — Origen: {{ $pagoNc->origen }}
                        @endif
                    </p>
                @endif

            @endif

            <hr>

            <p class="fw-bold text-success mb-0">
                <strong>Saldo pendiente actual:</strong>
                ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}
            </p>
        </div>
    </div>








    {{-- Sección de abonos: solo documentos que no sean NC --}}
    @if(!$esNotaCredito)
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light fw-bold">Abonos registrados</div>
            <div class="card-body">
                @if($documento->abonos->isEmpty())
                    <p class="text-muted">Sin abonos registrados.</p>
                @else
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha Abono</th>
                                <th>Monto</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documento->abonos as $abono)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}</td>
                                    <td>${{ number_format($abono->monto, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($abono->origen === 'referencia_nc')
                                            <span class="text-muted small">
                                                Generado por referencia. Quite la referencia para revertirlo.
                                            </span>
                                        @else
                                            <form action="{{ route('abonos.destroy', $abono->id) }}"
                                                  method="POST"
                                                  class="js-confirm-submit"
                                                  data-no-loader
                                                  data-confirm-title="Eliminar abono"
                                                  data-confirm-message="¿Seguro que deseas eliminar este abono?"
                                                  data-confirm-button="Eliminar Abono">
                                                @csrf
                                                @method('DELETE')

                                                @if (Auth::id() != 375)
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Eliminar
                                                    </button>
                                                @endif
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Sección de cruces --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light fw-bold">Cruces registrados</div>
            <div class="card-body">
                @if($documento->cruces->isEmpty())
                    <p class="text-muted">Sin cruces registrados.</p>
                @else
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha Cruce</th>
                                <th>Monto</th>
                                <th>Cliente</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documento->cruces as $cruce)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}</td>
                                    <td>${{ number_format($cruce->monto, 0, ',', '.') }}</td>
                                    <td>
                                        @if($cruce->documento)
                                            <span class="fw-semibold">
                                                Folio CxC {{ $cruce->documento->folio }}
                                            </span><br>

                                            <small class="text-muted">
                                                Cliente: {{ $cruce->cobranza?->razon_social ?? '—' }}
                                            </small><br>

                                            <small class="text-muted">
                                                RUT: {{ $cruce->cobranza?->rut_cliente ?? '—' }}
                                            </small>
                                        @else
                                            <span class="text-muted">Cruce sin documento CxC asociado</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <form action="{{ route('cruces.destroy', $cruce->id) }}"
                                              method="POST"
                                              class="js-confirm-submit"
                                              data-no-loader
                                              data-confirm-title="Eliminar cruce"
                                              data-confirm-message="¿Seguro que deseas eliminar este cruce?"
                                              data-confirm-button="Eliminar Cruce">
                                            @csrf
                                            @method('DELETE')

                                            @if (Auth::id() != 375)
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Eliminar
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif

    {{-- Referencias del Documento --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Referencias del Documento</div>
        <div class="card-body">

            {{-- Si este documento referencia a otro --}}
            @if($documento->referencia)
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <p class="mb-0">
                        <strong>Referencia a:</strong><br>
                        {{ $documento->referencia->tipoDocumento->nombre ?? 'Documento' }}
                        Folio <strong>{{ $documento->referencia->folio }}</strong> —
                        Monto: ${{ number_format($documento->referencia->monto_total, 0, ',', '.') }}
                    </p>

                    @if (Auth::id() != 375)
                        <form action="{{ route('finanzas_compras.quitar_referencia', $documento->id) }}"
                              method="POST"
                              class="js-confirm-submit"
                              data-no-loader
                              data-confirm-title="Quitar referencia"
                              data-confirm-message="¿Seguro que deseas quitar la referencia actual de este documento?"
                              data-confirm-button="Quitar referencia">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                Quitar referencia
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Si otros documentos referencian a este --}}
            @if($documento->referenciados->isNotEmpty())
                <p><strong>Referenciado por:</strong></p>

                <ul class="list-unstyled">
                    @foreach($documento->referenciados as $ref)
                        <li class="mb-2 border rounded p-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                {{ $ref->tipoDocumento->nombre ?? 'Documento' }}
                                Folio <strong>{{ $ref->folio }}</strong> —
                                Monto: ${{ number_format($ref->monto_total, 0, ',', '.') }}
                            </div>

                            @if (Auth::id() != 375)
                                <form action="{{ route('finanzas_compras.quitar_referencia', $ref->id) }}"
                                      method="POST"
                                      class="js-confirm-submit"
                                      data-no-loader
                                      data-confirm-title="Quitar referencia"
                                      data-confirm-message="¿Seguro que deseas quitar esta referencia?"
                                      data-confirm-button="Quitar referencia">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Quitar referencia
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Si no tiene ninguna referencia --}}
            @if(!$documento->referencia && $documento->referenciados->isEmpty())
                <p class="text-muted">Sin referencias asociadas.</p>
            @endif

            {{-- Asignar nueva referencia: solo para NC --}}
            @if($esNotaCredito && Auth::id() != 375)

                <hr>

                <h6 class="fw-bold text-primary">
                    Asignar nueva referencia
                </h6>

                @if($candidatosReferencia->isEmpty())
                    <p class="text-muted mb-0">
                        No existen facturas disponibles para este proveedor.
                    </p>
                @else

                    <form action="{{ route('finanzas_compras.asignar_referencia', $documento->id) }}"
                          method="POST"
                          class="row g-2 mt-1">
                        @csrf

                        <div class="col-md-9">
                            <select name="factura_id" class="form-select" required>
                                <option value="">Seleccione una factura</option>

                                @foreach($candidatosReferencia as $factura)
                                    <option value="{{ $factura->id }}">
                                        Folio {{ $factura->folio }}
                                        — ${{ number_format($factura->monto_total, 0, ',', '.') }}
                                        — {{ \Carbon\Carbon::parse($factura->fecha_docto)->format('d-m-Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar referencia
                            </button>
                        </div>
                    </form>

                @endif

            @endif

        </div>
    </div>

    {{-- Botón volver --}}
    <div class="text-center mt-4">
        <a href="{{ session('return_to_listado', route('finanzas_compras.index')) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

</div>

{{-- Modal reutilizable de confirmación --}}
{{-- Modal reutilizable de confirmación --}}
<div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-sm rounded-3">

            <div class="modal-header px-4 py-3 border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="confirmActionModalLabel">
                        Confirmar acción
                    </h5>

                    <div class="small text-muted mt-1">
                        Esta operación modificará la información del documento.
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    id="confirmActionCloseX"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body px-4 py-4">
                <div class="alert alert-warning border mb-0">
                    <div class="fw-semibold mb-1" id="confirmActionMessage">
                        ¿Seguro que deseas continuar?
                    </div>

                    <div class="small mb-0">
                        Revisa la acción antes de confirmar. Una vez enviada, la página se actualizará con los cambios.
                    </div>
                </div>
            </div>

            <div class="modal-footer px-4 py-3">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    id="confirmActionCancelBtn">
                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    id="confirmActionSubmitBtn">
                    Confirmar
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('confirmActionModal');
        const titleEl = document.getElementById('confirmActionModalLabel');
        const messageEl = document.getElementById('confirmActionMessage');
        const confirmBtn = document.getElementById('confirmActionSubmitBtn');
        const cancelBtn = document.getElementById('confirmActionCancelBtn');
        const closeXBtn = document.getElementById('confirmActionCloseX');

        if (!modalEl || !titleEl || !messageEl || !confirmBtn || !cancelBtn || !closeXBtn) {
            return;
        }

        let pendingForm = null;
        let isSubmitting = false;
        let bootstrap5ModalInstance = null;

        function hasJqueryModal() {
            return typeof window.jQuery !== 'undefined'
                && typeof window.jQuery.fn !== 'undefined'
                && typeof window.jQuery.fn.modal === 'function';
        }

        function hasBootstrap5Modal() {
            return typeof window.bootstrap !== 'undefined'
                && typeof window.bootstrap.Modal === 'function';
        }

        function showConfirmModal() {
            if (hasJqueryModal()) {
                window.jQuery(modalEl).modal('show');
                return;
            }

            if (hasBootstrap5Modal()) {
                bootstrap5ModalInstance = bootstrap5ModalInstance || new bootstrap.Modal(modalEl);
                bootstrap5ModalInstance.show();
                return;
            }

            if (pendingForm && window.confirm(messageEl.textContent || '¿Seguro que deseas continuar?')) {
                submitPendingForm();
            }
        }

        function hideConfirmModal() {
            if (hasJqueryModal()) {
                window.jQuery(modalEl).modal('hide');
                return;
            }

            if (hasBootstrap5Modal()) {
                bootstrap5ModalInstance = bootstrap5ModalInstance || new bootstrap.Modal(modalEl);
                bootstrap5ModalInstance.hide();
                return;
            }

            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
        }

        function resetConfirmState() {
            if (isSubmitting) {
                return;
            }

            pendingForm = null;

            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirmar';

            cancelBtn.disabled = false;
            closeXBtn.disabled = false;
        }

        function submitPendingForm() {
            if (!pendingForm || isSubmitting) {
                return;
            }

            isSubmitting = true;

            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Procesando...';

            cancelBtn.disabled = true;
            closeXBtn.disabled = true;

            window.pageLoader?.show({ timeout: 30000 });

            try {
                pendingForm.submit();
            } catch (error) {
                isSubmitting = false;
                window.pageLoader?.forceHide?.();

                confirmBtn.disabled = false;
                confirmBtn.textContent = pendingForm?.dataset?.confirmButton || 'Confirmar';

                cancelBtn.disabled = false;
                closeXBtn.disabled = false;

                hideConfirmModal();

                console.error('Error al enviar el formulario confirmado:', error);
            }
        }

        document.querySelectorAll('.js-confirm-submit').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (isSubmitting) {
                    return;
                }

                pendingForm = form;

                const title = form.dataset.confirmTitle || 'Confirmar acción';
                const message = form.dataset.confirmMessage || '¿Seguro que deseas continuar?';
                const buttonText = form.dataset.confirmButton || 'Confirmar';

                titleEl.textContent = title;
                messageEl.textContent = message;

                confirmBtn.disabled = false;
                confirmBtn.textContent = buttonText;

                cancelBtn.disabled = false;
                closeXBtn.disabled = false;

                showConfirmModal();
            });
        });

        confirmBtn.addEventListener('click', function () {
            submitPendingForm();
        });

        cancelBtn.addEventListener('click', function () {
            if (isSubmitting) {
                return;
            }

            hideConfirmModal();
            resetConfirmState();
        });

        closeXBtn.addEventListener('click', function () {
            if (isSubmitting) {
                return;
            }

            hideConfirmModal();
            resetConfirmState();
        });

        if (hasJqueryModal()) {
            window.jQuery(modalEl).on('hidden.bs.modal', function () {
                resetConfirmState();
            });
        } else {
            modalEl.addEventListener('hidden.bs.modal', function () {
                resetConfirmState();
            });
        }
    });
</script>





@endsection