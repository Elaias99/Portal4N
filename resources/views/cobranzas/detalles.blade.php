@extends('layouts.app')

@section('content')
<div class="container mt-4" style="max-width: 1100px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary mb-0">
            Detalles del Documento Financiero
        </h2>

        @if($documento->tipo_documento_id != 61 && Auth::id() != 375)
            <button type="button"
                    class="btn btn-outline-secondary"
                    data-toggle="modal"
                    data-target="#modalStatus-{{ $documento->id }}">
                Editar
            </button>

            @include('cobranzas.modal_status', ['doc' => $documento])
        @endif
    </div>

    {{-- Información general del documento --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Información general</div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Empresa:</strong> {{ $documento->empresa?->Nombre ?? 'Sin empresa' }}</p>
                    <p><strong>Razón Social:</strong> {{ $documento->razon_social }}</p>
                    <p><strong>RUT Cliente:</strong> {{ $documento->rut_cliente }}</p>
                    <p><strong>Tipo Documento:</strong> {{ $documento->tipoDocumento?->nombre ?? '-' }}</p>
                    <p><strong>Folio:</strong> {{ $documento->folio }}</p>
                </div>

                <div class="col-md-6">
                    <p>
                        <strong>Monto Total:</strong>
                        ${{ number_format($documento->monto_total, 0, ',', '.') }}
                    </p>

                    <p>
                        <strong>Saldo Pendiente:</strong>
                        ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}
                    </p>

                    <p>
                        <strong>Estado Actual:</strong>
                        {{ $documento->estado_visible === 'Factory' ? 'Factoring' : $documento->estado_visible }}
                    </p>

                    <p>
                        <strong>Fecha Documento:</strong>
                        {{ $documento->fecha_docto ? \Carbon\Carbon::parse($documento->fecha_docto)->format('d-m-Y') : '-' }}
                    </p>

                    <p>
                        <strong>Fecha Vencimiento:</strong>
                        {{ $documento->fecha_vencimiento ? \Carbon\Carbon::parse($documento->fecha_vencimiento)->format('d-m-Y') : '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen del cálculo del saldo pendiente --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">
            Resumen del cálculo del saldo pendiente
        </div>

        <div class="card-body">
            @php
                /*
                |--------------------------------------------------------------------------
                | Saldo base del documento
                |--------------------------------------------------------------------------
                | Mantiene la misma lógica central del modelo:
                | monto_total - notas de crédito + notas de débito.
                |--------------------------------------------------------------------------
                */
                $saldoBase = (int) ($documento->monto_total ?? 0);
                $saldoAntesMovimientos = $saldoBase;

                $lineasNotas = collect();

                foreach ($referencias['referenciadoPor'] as $ref) {
                    $montoNota = (int) ($ref->monto_total ?? 0);

                    if ((int) $ref->tipo_documento_id === 61) {
                        $saldoAntesMovimientos -= $montoNota;

                        $lineasNotas->push([
                            'texto' => 'Descuento por Nota de Crédito folio ' . $ref->folio,
                            'signo' => '-',
                            'monto' => $montoNota,
                        ]);
                    }

                    if ((int) $ref->tipo_documento_id === 56) {
                        $saldoAntesMovimientos += $montoNota;

                        $lineasNotas->push([
                            'texto' => 'Aumento por Nota de Débito folio ' . $ref->folio,
                            'signo' => '+',
                            'monto' => $montoNota,
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Movimientos reales existentes
                |--------------------------------------------------------------------------
                | Esta vista no utiliza movimientos_documentos para calcular saldo.
                | Solo muestra los registros operativos que continúan asociados:
                | Abonos, Cruces, Pagos, Pronto pagos y todos los Factorings.
                |--------------------------------------------------------------------------
                */
                $movimientosResumen = collect();

                foreach ($documento->abonos as $abono) {
                    $movimientosResumen->push([
                        'tipo' => 'Abono',
                        'fecha' => $abono->fecha_abono,
                        'fecha_orden' => $abono->fecha_abono,
                        'created_at_orden' => $abono->created_at,
                        'monto' => (int) ($abono->monto ?? 0),
                        'prioridad' => 10,
                        'registro_id' => (int) $abono->id,
                        'registro' => $abono,
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
                        'registro_id' => (int) $cruce->id,
                        'registro' => $cruce,
                    ]);
                }

                foreach ($documento->factories as $factory) {
                    $movimientosResumen->push([
                        'tipo' => 'Factoring',
                        'fecha' => $factory->fecha_factory,
                        'fecha_orden' => $factory->fecha_factory,
                        'created_at_orden' => $factory->created_at,
                        'monto' => null,
                        'monto_cedido' => (int) ($factory->monto ?? 0),
                        'saldo_liquido' => (int) ($factory->saldo_liquido ?? 0),
                        'monto_no_anticipado' => (int) ($factory->monto_no_anticipado ?? 0),
                        'diferencia_precio' => (int) ($factory->diferencia_precio ?? 0),
                        'cesion' => $factory->cesion,
                        'prioridad' => 30,
                        'registro_id' => (int) $factory->id,
                        'registro' => $factory,
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
                        'registro_id' => (int) $prontoPago->id,
                        'registro' => $prontoPago,
                    ]);
                }

                foreach ($documento->pagos as $pago) {
                    $movimientosResumen->push([
                        'tipo' => 'Pago',
                        'fecha' => $pago->fecha_pago,
                        'fecha_orden' => $pago->fecha_pago,
                        'created_at_orden' => $pago->created_at,
                        'monto' => null,
                        'prioridad' => 50,
                        'registro_id' => (int) $pago->id,
                        'registro' => $pago,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Orden de presentación
                |--------------------------------------------------------------------------
                | Se mantiene el mismo criterio utilizado por el modelo para saldo:
                | created_at determina la secuencia real de registro y la fecha propia
                | del movimiento se utiliza solo si no existe created_at.
                |--------------------------------------------------------------------------
                */
                $movimientosResumen = $movimientosResumen
                    ->sortBy(function ($item) {
                        $createdAt = $item['created_at_orden']
                            ? \Carbon\Carbon::parse($item['created_at_orden'])->format('Y-m-d H:i:s')
                            : null;

                        $fechaMovimiento = $item['fecha_orden']
                            ? \Carbon\Carbon::parse($item['fecha_orden'])->format('Y-m-d') . ' 00:00:00'
                            : '0000-00-00 00:00:00';

                        return ($createdAt ?? $fechaMovimiento)
                            . ' '
                            . str_pad($item['prioridad'], 2, '0', STR_PAD_LEFT)
                            . ' '
                            . str_pad($item['registro_id'], 20, '0', STR_PAD_LEFT);
                    })
                    ->values();

                $saldoCalculado = max($saldoAntesMovimientos, 0);
            @endphp

            {{-- Monto inicial --}}
            <p class="mb-1">
                <strong>Monto total inicial:</strong>
                ${{ number_format($documento->monto_total, 0, ',', '.') }}
            </p>

            {{-- Notas de crédito / débito --}}
            @foreach($lineasNotas as $lineaNota)
                <p class="mb-1">
                    <strong>{{ $lineaNota['texto'] }}:</strong>
                    {{ $lineaNota['signo'] }} ${{ number_format($lineaNota['monto'], 0, ',', '.') }}
                </p>
            @endforeach

            @if($movimientosResumen->isEmpty())
                <p class="text-muted mt-3 mb-0">
                    Sin movimientos de gestión registrados.
                </p>
            @else
                <hr>

                {{-- Estados / movimientos ordenados --}}
                @foreach($movimientosResumen as $movimiento)
                    @php
                        $fechaMovimiento = $movimiento['fecha']
                            ? \Carbon\Carbon::parse($movimiento['fecha'])->format('d-m-Y')
                            : '-';

                        $registroMovimiento = $movimiento['registro'] ?? null;

                        $saldoAntesMovimiento = max((int) $saldoCalculado, 0);
                        $montoMovimiento = 0;

                        if (in_array($movimiento['tipo'], ['Abono', 'Cruce'], true)) {
                            $montoMovimiento = min(
                                (int) ($movimiento['monto'] ?? 0),
                                $saldoAntesMovimiento
                            );

                            $saldoCalculado = max(
                                $saldoAntesMovimiento - $montoMovimiento,
                                0
                            );
                        }

                        if ($movimiento['tipo'] === 'Factoring') {
                            $saldoLiquidoFactory = (int) ($movimiento['saldo_liquido'] ?? 0);
                            $montoNoAnticipadoFactory = (int) ($movimiento['monto_no_anticipado'] ?? 0);

                            $montoMovimiento = min(
                                $saldoLiquidoFactory + $montoNoAnticipadoFactory,
                                $saldoAntesMovimiento
                            );

                            $saldoCalculado = max(
                                $saldoAntesMovimiento - $montoMovimiento,
                                0
                            );
                        }

                        if (in_array($movimiento['tipo'], ['Pago', 'Pronto pago'], true)) {
                            $montoMovimiento = $saldoAntesMovimiento;
                            $saldoCalculado = 0;
                        }
                    @endphp

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            @if($movimiento['tipo'] === 'Factoring')
                                <p class="mb-1">
                                    <strong>
                                        Factoring registrado el {{ $fechaMovimiento }}
                                        @if(!empty($movimiento['cesion']))
                                            — Cesión {{ $movimiento['cesion'] }}
                                        @endif:
                                    </strong>
                                </p>

                                <div class="small text-muted ms-2">
                                    <div>
                                        Monto cedido:
                                        ${{ number_format((int) ($movimiento['monto_cedido'] ?? 0), 0, ',', '.') }}
                                    </div>

                                    <div>
                                        Monto líquido:
                                        - ${{ number_format((int) ($movimiento['saldo_liquido'] ?? 0), 0, ',', '.') }}
                                    </div>

                                    <div>
                                        Monto no anticipado:
                                        - ${{ number_format((int) ($movimiento['monto_no_anticipado'] ?? 0), 0, ',', '.') }}
                                    </div>

                                    <div class="fw-semibold text-dark">
                                        Diferencia de precio pendiente:
                                        ${{ number_format((int) ($movimiento['diferencia_precio'] ?? 0), 0, ',', '.') }}
                                    </div>
                                </div>
                            @else
                                <p class="mb-1">
                                    <strong>{{ $movimiento['tipo'] }} registrado el {{ $fechaMovimiento }}:</strong>
                                    - ${{ number_format($montoMovimiento, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>

                        @if($movimiento['tipo'] === 'Pago' && $registroMovimiento && Auth::id() != 375)
                            <form action="{{ route('pagos.destroy', $registroMovimiento->id) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar este pago y recalcular el saldo del documento?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Eliminar Pago
                                </button>
                            </form>

                        @elseif($movimiento['tipo'] === 'Pronto pago' && $registroMovimiento && Auth::id() != 375)
                            <form action="{{ route('prontopagos.destroy', $registroMovimiento->id) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar este pronto pago y recalcular el saldo del documento?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Eliminar Pronto Pago
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            @endif

            <hr>

            <p class="fw-bold text-success mb-0">
                <strong>Saldo pendiente actual:</strong>
                ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Sección de abonos --}}
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
                                    <form action="{{ route('abonos.destroy', $abono->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este abono?')">
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
                            <th>Proveedor</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($documento->cruces as $cruce)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}</td>
                                <td>${{ number_format($cruce->monto, 0, ',', '.') }}</td>

                                <td>
                                    @if($cruce->cobranza)
                                        <span class="fw-semibold">{{ $cruce->cobranza->razon_social }}</span><br>
                                        <small class="text-muted">RUT: {{ $cruce->cobranza->rut_cliente }}</small>
                                    @else
                                        <span class="text-muted">— Sin cliente —</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <form action="{{ route('cruces.destroy', $cruce->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este cruce?')">
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

    {{-- Sección de Factoring --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
            <span>Factoring registrados</span>

            @if($documento->factories->isNotEmpty())
                <span class="badge bg-secondary">
                    {{ $documento->factories->count() }}
                </span>
            @endif
        </div>

        <div class="card-body">
            @if($documento->factories->isEmpty())
                <p class="text-muted mb-0">Sin Factoring registrado.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha Factoring</th>
                                <th>Nombre Factoring / Banco</th>
                                <th>RUT Factoring</th>
                                <th>Cesión</th>
                                <th class="text-end">Monto cedido</th>
                                <th class="text-end">Saldo líquido</th>
                                <th class="text-end">Monto no anticipado</th>
                                <th class="text-end">Diferencia de precio</th>
                                <th class="text-end">Comisión total</th>
                                <th class="text-end">Monto a recibir</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($documento->factories->sortByDesc('created_at') as $factory)
                                <tr>
                                    <td>
                                        {{ $factory->fecha_factory
                                            ? \Carbon\Carbon::parse($factory->fecha_factory)->format('d-m-Y')
                                            : '-' }}
                                    </td>

                                    <td>{{ $factory->banco?->nombre ?? 'Sin banco' }}</td>
                                    <td>{{ $factory->rut_factory ?: '-' }}</td>
                                    <td>{{ $factory->cesion ?? '-' }}</td>

                                    <td class="text-end">
                                        ${{ number_format((int) ($factory->monto ?? 0), 0, ',', '.') }}
                                    </td>

                                    <td class="text-end">
                                        ${{ number_format((int) ($factory->saldo_liquido ?? 0), 0, ',', '.') }}
                                    </td>

                                    <td class="text-end">
                                        ${{ number_format((int) ($factory->monto_no_anticipado ?? 0), 0, ',', '.') }}
                                    </td>

                                    <td class="text-end fw-semibold">
                                        ${{ number_format((int) ($factory->diferencia_precio ?? 0), 0, ',', '.') }}
                                    </td>

                                    <td class="text-end">
                                        ${{ number_format((int) ($factory->comision_total ?? 0), 0, ',', '.') }}
                                    </td>

                                    <td class="text-end">
                                        ${{ number_format((int) ($factory->monto_a_recibir ?? 0), 0, ',', '.') }}
                                    </td>

                                    <td class="text-center">
                                        <form action="{{ route('factories.destroy', $factory->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este Factoring y recalcular los movimientos restantes?')">
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
                </div>
            @endif
        </div>
    </div>

    {{-- Referencias --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Referencias del documento</div>

        <div class="card-body">
            @if($referencias['referencia'])
                <p>
                    <strong>Este documento referencia a:</strong>
                    Folio {{ $referencias['referencia']->folio }}
                    ({{ $referencias['referencia']->tipoDocumento?->nombre ?? 'Sin tipo' }})
                    por ${{ number_format($referencias['referencia']->monto_total, 0, ',', '.') }}
                </p>
            @endif

            @if($referencias['referenciadoPor']->isNotEmpty())
                <p><strong>Este documento es referenciado por:</strong></p>

                <ul>
                    @foreach ($referencias['referenciadoPor'] as $ref)
                        <li>
                            {{ (int) $ref->tipo_documento_id === 56 ? 'Nota de débito' : 'Nota de crédito' }}
                            folio {{ $ref->folio }}
                            por ${{ number_format($ref->monto_total, 0, ',', '.') }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(!$referencias['referencia'] && $referencias['referenciadoPor']->isEmpty())
                <p class="text-muted">Sin referencias asociadas.</p>
            @endif
        </div>
    </div>

    {{-- Botón para volver --}}
    <div class="text-center mt-4">
        <a href="{{ session('return_to_listado', url('/cobranzas/documentos')) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

</div>

@vite('resources/js/cobranzas_documentos.js')

@endsection