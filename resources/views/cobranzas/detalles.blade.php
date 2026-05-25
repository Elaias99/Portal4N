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
                    <p><strong>Monto Total:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>
                    <p><strong>Saldo Pendiente:</strong> ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}</p>

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
        <div class="card-header bg-light fw-bold">Resumen del cálculo del saldo pendiente</div>
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

                $movimientosResumen = collect();

                foreach ($documento->abonos as $abono) {
                    $movimientosResumen->push([
                        'tipo' => 'Abono',
                        'fecha' => $abono->fecha_abono,
                        'fecha_orden' => $abono->fecha_abono,
                        'created_at_orden' => $abono->created_at,
                        'monto' => (int) ($abono->monto ?? 0),
                        'prioridad' => 10,
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
                    ]);
                }

                foreach ($documento->pagos as $pago) {
                    $movimientosResumen->push([
                        'tipo' => 'Pago',
                        'fecha' => $pago->fecha_pago,
                        'fecha_orden' => $pago->fecha_pago,
                        'created_at_orden' => $pago->created_at,
                        'monto' => null,
                        'prioridad' => 30,
                        'registro' => $pago,
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
                    ]);
                }

                if ($documento->factoryRegistro) {
                    $factory = $documento->factoryRegistro;

                    $movimientosResumen->push([
                        'tipo' => 'Factoring',
                        'fecha' => $factory->fecha_factory,
                        'fecha_orden' => $factory->fecha_factory,
                        'created_at_orden' => $factory->created_at,
                        'monto' => (int) ($factory->saldo_liquido ?? 0),
                        'prioridad' => 50,
                    ]);
                }

                $movimientosResumen = $movimientosResumen
                    ->sortBy(function ($item) {
                        $createdAt = $item['created_at_orden']
                            ? \Carbon\Carbon::parse($item['created_at_orden'])->format('Y-m-d H:i:s')
                            : null;

                        $fechaMovimiento = $item['fecha_orden']
                            ? \Carbon\Carbon::parse($item['fecha_orden'])->format('Y-m-d') . ' 00:00:00'
                            : '9999-12-31 00:00:00';

                        return ($createdAt ?? $fechaMovimiento) . ' ' . str_pad($item['prioridad'], 2, '0', STR_PAD_LEFT);
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

                    $registroMovimiento = $movimiento['registro'] ?? null;
                @endphp

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <p class="mb-1">
                        <strong>{{ $movimiento['tipo'] }} registrado el {{ $fechaMovimiento }}:</strong>
                        - ${{ number_format($montoMovimiento, 0, ',', '.') }}
                    </p>





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
        <div class="card-header bg-light fw-bold">Factoring registrado</div>
        <div class="card-body">
            @if(!$documento->factoryRegistro)
                <p class="text-muted">Sin Factoring registrado.</p>
            @else
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha Factoring</th>
                            <th>Nombre Factoring / Banco</th>
                            <th>RUT Factoring</th>
                            <th>Cesión</th>
                            <th class="text-end">Monto cedido</th>
                            <th class="text-end">Saldo líquido</th>
                            <th class="text-end">Diferencia</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {{ $documento->factoryRegistro->fecha_factory
                                    ? \Carbon\Carbon::parse($documento->factoryRegistro->fecha_factory)->format('d-m-Y')
                                    : '-' }}
                            </td>

                            <td>{{ $documento->factoryRegistro->banco?->nombre ?? 'Sin banco' }}</td>
                            <td>{{ $documento->factoryRegistro->rut_factory }}</td>
                            <td>{{ $documento->factoryRegistro->cesion ?? '-' }}</td>

                            <td class="text-end">
                                ${{ number_format($documento->factoryRegistro->monto ?? 0, 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                ${{ number_format($documento->factoryRegistro->saldo_liquido ?? 0, 0, ',', '.') }}
                            </td>

                            <td class="text-end">
                                ${{ number_format($documento->factoryRegistro->diferencia ?? 0, 0, ',', '.') }}
                            </td>

                            <td class="text-center">
                                <form action="{{ route('factories.destroy', $documento->factoryRegistro->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar este Factoring?')">
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
                    </tbody>
                </table>
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
                            Nota de crédito folio {{ $ref->folio }}
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
@endsection