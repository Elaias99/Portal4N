@extends('layouts.app')

@section('content')

@php
    $formatoMonto = fn ($valor) => '$' . number_format((int) ($valor ?? 0), 0, ',', '.');

    $formatoFecha = fn ($fecha) => $fecha
        ? \Carbon\Carbon::parse($fecha)->format('d-m-Y')
        : '-';

    $mostrarEstado = fn ($estado) => $estado === 'Factory'
        ? 'Factoring'
        : ($estado ?: '—');
@endphp

<div class="container mt-4" style="max-width: 1100px;">





    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary mb-0">
            Detalles del Documento Financiero
        </h2>

        @php
            $tieneOperacionFactoryCerrada = $documento->factories
                ->contains(fn ($factory) => $factory->estado_operacion === 'Cerrada');
        @endphp

        @if($documento->tipo_documento_id != 61 && Auth::id() != 375)
            @if($tieneOperacionFactoryCerrada)
                <button type="button"
                        class="btn btn-outline-secondary"
                        disabled
                        title="No se puede editar porque la operación Factoring está cerrada.">
                    Editar
                </button>
            @else
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-toggle="modal"
                        data-target="#modalStatus-{{ $documento->id }}">
                    Editar
                </button>

                @include('cobranzas.modal_status', ['doc' => $documento])
            @endif
        @endif
    </div>





    {{-- Información general --}}
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
                    <p><strong>Monto Total:</strong> {{ $formatoMonto($documento->monto_total) }}</p>

                    <p>
                        <strong>Saldo Pendiente:</strong>
                        <span class="{{ (int) $documento->saldo_pendiente <= 0 ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                            {{ $formatoMonto($documento->saldo_pendiente) }}
                        </span>
                    </p>

                    <p><strong>Estado Actual:</strong> {{ $mostrarEstado($documento->estado_visible) }}</p>
                    <p><strong>Fecha Documento:</strong> {{ $formatoFecha($documento->fecha_docto) }}</p>
                    <p><strong>Fecha Vencimiento:</strong> {{ $formatoFecha($documento->fecha_vencimiento) }}</p>
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
                $saldoBase = (int) ($documento->monto_total ?? 0);
                $saldoAntesMovimientos = $saldoBase;
                $lineasNotas = collect();

                foreach ($referencias['referenciadoPor'] as $ref) {
                    $montoNota = (int) ($ref->monto_total ?? 0);

                    if ((int) $ref->tipo_documento_id === 61) {
                        $saldoAntesMovimientos -= $montoNota;

                        $lineasNotas->push([
                            'texto' => 'Nota de Crédito folio ' . $ref->folio,
                            'signo' => '-',
                            'monto' => $montoNota,
                        ]);
                    }

                    if ((int) $ref->tipo_documento_id === 56) {
                        $saldoAntesMovimientos += $montoNota;

                        $lineasNotas->push([
                            'texto' => 'Nota de Débito folio ' . $ref->folio,
                            'signo' => '+',
                            'monto' => $montoNota,
                        ]);
                    }
                }

                $movimientosResumen = collect();

                foreach ($documento->abonos as $abono) {
                    $movimientosResumen->push([
                        'tipo' => 'Abono',
                        'fecha' => $abono->fecha_abono,
                        'created_at' => $abono->created_at,
                        'monto' => (int) ($abono->monto ?? 0),
                        'detalle' => 'Abono registrado',
                        'registro' => $abono,
                        'prioridad' => 10,
                    ]);
                }

                foreach ($documento->cruces as $cruce) {
                    $movimientosResumen->push([
                        'tipo' => 'Cruce',
                        'fecha' => $cruce->fecha_cruce,
                        'created_at' => $cruce->created_at,
                        'monto' => (int) ($cruce->monto ?? 0),
                        'detalle' => 'Cruce registrado',
                        'registro' => $cruce,
                        'prioridad' => 20,
                    ]);
                }

                foreach ($documento->factories as $factory) {
                    $detalleFactoring = 'Factoring registrado';

                    if ($factory->cesion) {
                        $detalleFactoring .= ' · Cesión ' . $factory->cesion;
                    }

                    if ($factory->banco?->nombre) {
                        $detalleFactoring .= ' · ' . $factory->banco->nombre;
                    }

                    $movimientosResumen->push([
                        'tipo' => 'Factoring',
                        'fecha' => $factory->fecha_factory,
                        'created_at' => $factory->created_at,
                        'monto' => null,
                        'detalle' => $detalleFactoring,
                        'diferencia_precio' => (int) ($factory->diferencia_precio ?? 0),
                        'registro' => $factory,
                        'prioridad' => 30,
                    ]);
                }

                foreach ($documento->prontoPagos as $prontoPago) {
                    $movimientosResumen->push([
                        'tipo' => 'Pronto pago',
                        'fecha' => $prontoPago->fecha_pronto_pago,
                        'created_at' => $prontoPago->created_at,
                        'monto' => null,
                        'detalle' => 'Pronto pago registrado',
                        'registro' => $prontoPago,
                        'prioridad' => 40,
                    ]);
                }

                foreach ($documento->pagos as $pago) {
                    $movimientosResumen->push([
                        'tipo' => 'Pago',
                        'fecha' => $pago->fecha_pago,
                        'created_at' => $pago->created_at,
                        'monto' => null,
                        'detalle' => 'Pago registrado',
                        'registro' => $pago,
                        'prioridad' => 50,
                    ]);
                }

                $movimientosResumen = $movimientosResumen
                    ->sortBy(function ($item) {
                        $fechaOrden = $item['created_at']
                            ? \Carbon\Carbon::parse($item['created_at'])->format('Y-m-d H:i:s')
                            : (
                                $item['fecha']
                                    ? \Carbon\Carbon::parse($item['fecha'])->format('Y-m-d') . ' 00:00:00'
                                    : '0000-00-00 00:00:00'
                            );

                        return $fechaOrden . '-' . str_pad($item['prioridad'], 2, '0', STR_PAD_LEFT);
                    })
                    ->values();

                $saldoCalculado = max($saldoAntesMovimientos, 0);
                $lineasSaldo = collect();

                foreach ($movimientosResumen as $movimiento) {
                    $saldoAntes = max((int) $saldoCalculado, 0);
                    $saldoDespues = $saldoAntes;
                    $montoAplicado = 0;

                    if (in_array($movimiento['tipo'], ['Abono', 'Cruce'], true)) {
                        $montoAplicado = min((int) ($movimiento['monto'] ?? 0), $saldoAntes);
                        $saldoDespues = max($saldoAntes - $montoAplicado, 0);
                    }

                    if ($movimiento['tipo'] === 'Factoring') {
                        $saldoDespues = max((int) ($movimiento['diferencia_precio'] ?? 0), 0);
                        $montoAplicado = max($saldoAntes - $saldoDespues, 0);
                    }

                    if (in_array($movimiento['tipo'], ['Pago', 'Pronto pago'], true)) {
                        $montoAplicado = $saldoAntes;
                        $saldoDespues = 0;
                    }

                    $lineasSaldo->push([
                        'fecha' => $movimiento['fecha'],
                        'tipo' => $movimiento['tipo'],
                        'detalle' => $movimiento['detalle'],
                        'monto_aplicado' => $montoAplicado,
                        'saldo_despues' => $saldoDespues,
                        'registro' => $movimiento['registro'],
                    ]);

                    $saldoCalculado = $saldoDespues;
                }
            @endphp

            <table class="table table-sm table-bordered align-middle">
                <tbody>
                    <tr>
                        <th style="width: 280px;">Monto total inicial</th>
                        <td class="text-end fw-semibold">{{ $formatoMonto($documento->monto_total) }}</td>
                    </tr>

                    @foreach($lineasNotas as $lineaNota)
                        <tr>
                            <th>{{ $lineaNota['texto'] }}</th>
                            <td class="text-end">
                                {{ $lineaNota['signo'] }} {{ $formatoMonto($lineaNota['monto']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($lineasSaldo->isEmpty())
                <p class="text-muted mb-0">Sin movimientos de gestión registrados.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Movimiento</th>
                                <th>Detalle</th>
                                <th class="text-end">Monto aplicado</th>
                                <th class="text-end">Saldo después</th>
                                <th class="text-center" style="width: 150px;">Acción</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($lineasSaldo as $linea)
                                @php($registroMovimiento = $linea['registro'])

                                <tr>
                                    <td>{{ $formatoFecha($linea['fecha']) }}</td>
                                    <td class="fw-semibold">{{ $linea['tipo'] }}</td>
                                    <td>{{ $linea['detalle'] }}</td>

                                    <td class="text-end">
                                        {{ (int) $linea['monto_aplicado'] > 0 ? '- ' . $formatoMonto($linea['monto_aplicado']) : $formatoMonto(0) }}
                                    </td>

                                    <td class="text-end fw-bold {{ (int) $linea['saldo_despues'] <= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $formatoMonto($linea['saldo_despues']) }}
                                    </td>

                                    <td class="text-center">
                                        @if($linea['tipo'] === 'Pago' && $registroMovimiento && Auth::id() != 375)
                                            <form action="{{ route('pagos.destroy', $registroMovimiento->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Seguro que deseas eliminar este pago y recalcular el saldo del documento?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                            </form>
                                        @elseif($linea['tipo'] === 'Pronto pago' && $registroMovimiento && Auth::id() != 375)
                                            <form action="{{ route('prontopagos.destroy', $registroMovimiento->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Seguro que deseas eliminar este pronto pago y recalcular el saldo del documento?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Saldo pendiente actual</th>
                                <th class="text-end fw-bold {{ (int) $documento->saldo_pendiente <= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $formatoMonto($documento->saldo_pendiente) }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Abonos --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Abonos registrados</div>

        <div class="card-body">
            @if($documento->abonos->isEmpty())
                <p class="text-muted mb-0">Sin abonos registrados.</p>
            @else
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha Abono</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($documento->abonos as $abono)
                            <tr>
                                <td>{{ $formatoFecha($abono->fecha_abono) }}</td>
                                <td class="text-end">{{ $formatoMonto($abono->monto) }}</td>

                                <td class="text-center">
                                    <form action="{{ route('abonos.destroy', $abono->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este abono?')">
                                        @csrf
                                        @method('DELETE')

                                        @if (Auth::id() != 375)
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
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

    {{-- Cruces --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Cruces registrados</div>

        <div class="card-body">
            @if($documento->cruces->isEmpty())
                <p class="text-muted mb-0">Sin cruces registrados.</p>
            @else
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha Cruce</th>
                            <th class="text-end">Monto</th>
                            <th>Referencia</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($documento->cruces as $cruce)
                            <tr>
                                <td>{{ $formatoFecha($cruce->fecha_cruce) }}</td>
                                <td class="text-end">{{ $formatoMonto($cruce->monto) }}</td>

                                <td>
                                    @if($cruce->cobranza)
                                        <span class="fw-semibold">{{ $cruce->cobranza->razon_social }}</span><br>
                                        <small class="text-muted">RUT: {{ $cruce->cobranza->rut_cliente }}</small>
                                    @elseif($cruce->documentoCompra)
                                        <span class="fw-semibold">CxP Folio {{ $cruce->documentoCompra->folio }}</span><br>
                                        <small class="text-muted">{{ $cruce->documentoCompra->razon_social ?? 'Documento de compra asociado' }}</small>
                                    @else
                                        <span class="text-muted">— Sin referencia —</span>
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
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
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

    {{-- Factoring --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
            <span>Factoring registrados</span>

            @if($documento->factories->isNotEmpty())
                <span class="badge bg-secondary">
                    {{ $documento->factories->count() }}
                    {{ $documento->factories->count() === 1 ? 'movimiento' : 'movimientos' }}
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
                                <th>Fecha</th>
                                <th>Banco / Factoring</th>
                                <th>Cesión</th>
                                <th class="text-end">Monto cedido</th>
                                <th class="text-end">Monto anticipado</th>
                                <th class="text-end">No anticipado</th>
                                <th class="text-end">Dif. precio</th>
                                <th class="text-end">Comisión</th>
                                <th class="text-end">Monto a recibir</th>
                                <th>Usuario</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($documento->factories->sortByDesc('created_at') as $factory)
                                <tr>
                                    <td>{{ $formatoFecha($factory->fecha_factory) }}</td>
                                    <td>{{ $factory->banco?->nombre ?? 'Sin banco' }}</td>
                                    <td>{{ $factory->cesion ?? '-' }}</td>

                                    <td class="text-end">{{ $formatoMonto($factory->monto) }}</td>
                                    <td class="text-end">{{ $formatoMonto($factory->saldo_liquido) }}</td>
                                    <td class="text-end">{{ $formatoMonto($factory->monto_no_anticipado) }}</td>
                                    <td class="text-end fw-semibold">{{ $formatoMonto($factory->diferencia_precio) }}</td>
                                    <td class="text-end">{{ $formatoMonto($factory->comision_total) }}</td>
                                    <td class="text-end fw-semibold text-success">{{ $formatoMonto($factory->monto_a_recibir) }}</td>
                                    <td>{{ $factory->usuario?->name ?? '—' }}</td>

                                    <td class="text-center">
                                        <form action="{{ route('factories.destroy', $factory->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este Factoring y recalcular los movimientos restantes?')">
                                            @csrf
                                            @method('DELETE')

                                            @if (Auth::id() != 375)
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($documento->factories->count() > 1)
                    <p class="small text-muted mt-2 mb-0">
                        Este documento tiene más de un movimiento de Factoring registrado. Cada fila representa un movimiento real asociado al documento.
                    </p>
                @endif
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
                    por {{ $formatoMonto($referencias['referencia']->monto_total) }}
                </p>
            @endif

            @if($referencias['referenciadoPor']->isNotEmpty())
                <p><strong>Este documento es referenciado por:</strong></p>

                <ul>
                    @foreach ($referencias['referenciadoPor'] as $ref)
                        <li>
                            {{ (int) $ref->tipo_documento_id === 56 ? 'Nota de débito' : 'Nota de crédito' }}
                            folio {{ $ref->folio }}
                            por {{ $formatoMonto($ref->monto_total) }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(!$referencias['referencia'] && $referencias['referenciadoPor']->isEmpty())
                <p class="text-muted mb-0">Sin referencias asociadas.</p>
            @endif
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="{{ session('return_to_listado', url('/cobranzas/documentos')) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

</div>

@vite('resources/js/cobranzas_documentos.js')

@endsection