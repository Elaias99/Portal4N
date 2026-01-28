@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalle del Honorario</h2>

        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalEstadoHonorario"
                id="btn-editar-honorario">
            Editar
        </button>
    </div>




    

    {{-- =========================
    INFORMACIÓN GENERAL
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Información general</strong>
        </div>




        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Empresa:</strong> {{ $honorario->empresa->Nombre ?? '-' }}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Monto total (SII):</strong>
                    ${{ number_format($honorario->monto_pagado ?? 0, 0, ',', '.') }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Emisor:</strong> {{ $honorario->razon_social_emisor }}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Saldo pendiente actual:</strong>
                    <span class="{{ $honorario->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($honorario->saldo_pendiente ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>RUT Emisor:</strong> {{ $honorario->rut_emisor }}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Estado actual:</strong>
                    {{ $honorario->estado_financiero ?? $honorario->estado_financiero_inicial }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Folio:</strong> {{ $honorario->folio }}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Fecha emisión:</strong>
                    {{ $honorario->fecha_emision?->format('d-m-Y') }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <strong>Estado SII:</strong>
                    <span class="{{ $honorario->estado === 'ANULADA' ? 'text-danger' : 'text-success' }}">
                        {{ $honorario->estado }}
                    </span>
                </div>
                <div class="col-md-6 text-end">
                    <strong>Fecha vencimiento:</strong>
                    {{ $honorario->fecha_vencimiento?->format('d-m-Y') ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
    RESUMEN CRONOLÓGICO DEL SALDO
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Resumen cronológico del cálculo del saldo</strong>
        </div>

        <div class="card-body">

            @php
                // Monto inicial
                $saldo = $honorario->monto_pagado ?? 0;

                // Construir línea de tiempo
                $movimientos = collect();

                foreach ($honorario->abonos as $a) {
                    $movimientos->push([
                        'fecha' => $a->fecha_abono,
                        'tipo'  => 'Abono',
                        'monto' => $a->monto,
                    ]);
                }

                foreach ($honorario->cruces as $c) {
                    $movimientos->push([
                        'fecha' => $c->fecha_cruce,
                        'tipo'  => 'Cruce',
                        'monto' => $c->monto,
                    ]);
                }

                foreach ($honorario->pagos as $p) {
                    $movimientos->push([
                        'fecha' => $p->fecha_pago,
                        'tipo'  => 'Pago',
                        'monto' => null,
                    ]);
                }

                foreach ($honorario->prontoPagos as $pp) {
                    $movimientos->push([
                        'fecha' => $pp->fecha_pronto_pago,
                        'tipo'  => 'Pronto pago',
                        'monto' => null,
                    ]);
                }

                $movimientos = $movimientos->sortBy('fecha');
            @endphp

            <p>
                <strong>Monto inicial (SII):</strong>
                ${{ number_format($saldo, 0, ',', '.') }}
            </p>

            <ul class="list-group mb-3">
                @foreach($movimientos as $m)
                    <li class="list-group-item">

                        <strong>{{ \Carbon\Carbon::parse($m['fecha'])->format('d-m-Y') }}</strong>
                        — {{ $m['tipo'] }}

                        @if($m['monto'])
                            : -${{ number_format($m['monto'], 0, ',', '.') }}
                            @php $saldo -= $m['monto']; @endphp
                        @else
                            (cierre del documento)
                            @php $saldo = 0; @endphp
                        @endif

                        <div class="text-muted small">
                            Saldo después del movimiento:
                            ${{ number_format($saldo, 0, ',', '.') }}
                        </div>
                    </li>
                @endforeach
            </ul>

            <p class="fw-bold">
                Saldo pendiente final:
                <span class="{{ $honorario->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($honorario->saldo_pendiente ?? 0, 0, ',', '.') }}
                </span>
            </p>

        </div>
    </div>

    {{-- =========================
    ABONOS (DETALLE)
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Abonos registrados</strong>
        </div>

        <div class="card-body p-0">
            @if($honorario->abonos->isEmpty())
                <p class="p-3 text-muted mb-0">No hay abonos registrados.</p>
            @else
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($honorario->abonos as $abono)
                        <tr>
                            <td>{{ $abono->fecha_abono }}</td>

                            <td class="text-end">
                                ${{ number_format($abono->monto, 0, ',', '.') }}
                            </td>

                            <td class="text-center">
                                <form method="POST"
                                    action="{{ route('honorarios.mensual.abono.revertir', $abono->id) }}"
                                    onsubmit="return confirm('¿Estás seguro de eliminar este abono?');"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-outline-danger btn-sm">
                                        Eliminar
                                    </button>
                                </form>

                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            @endif
        </div>
    </div>

    {{-- =========================
    CRUCES (DETALLE)
    ========================== --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Cruces registrados</strong>
        </div>

        <div class="card-body p-0">
            @if($honorario->cruces->isEmpty())
                <p class="p-3 text-muted mb-0">No hay cruces registrados.</p>
            @else
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($honorario->cruces as $cruce)
                            <tr>
                                <td>{{ $cruce->fecha_cruce }}</td>
                                <td class="text-end">
                                    ${{ number_format($cruce->monto, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- =========================
    VOLVER
    ========================== --}}
    <div class="text-center mt-4">
        <a href="{{ route('honorarios.mensual.index') }}"
           class="btn btn-outline-primary px-4 py-2 rounded-pill">
            ← Volver a Honorarios Mensuales
        </a>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const btn = document.getElementById('btn-editar-honorario');
        if (!btn) return;

        btn.addEventListener('click', function () {

            document.getElementById('modal-honorario-id').value = '{{ $honorario->id }}';
            document.getElementById('modal-emisor').value = '{{ $honorario->razon_social_emisor }}';
            document.getElementById('modal-estado-actual').value =
                '{{ $honorario->estado_financiero ?? $honorario->estado_financiero_inicial }}';
            document.getElementById('modal-saldo').value =
                '{{ number_format($honorario->saldo_pendiente ?? 0, 0, ",", ".") }}';

            // reset selector
            document.getElementById('modal-nuevo-estado').value = '';

            // ocultar bloques
            ['modal-campo-abono','modal-campo-cruce','modal-campo-pago','modal-campo-pronto-pago']
                .forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.classList.add('d-none');
                    el.querySelectorAll('input, select').forEach(i => i.disabled = true);
                });
        });
    });
</script>


<script>
    document.addEventListener('change', function (e) {

        if (e.target.id !== 'modal-nuevo-estado') return;

        const estado = e.target.value;

        const bloques = {
            'Abono': 'modal-campo-abono',
            'Cruce': 'modal-campo-cruce',
            'Pago': 'modal-campo-pago',
            'Pronto pago': 'modal-campo-pronto-pago',
        };

        // Ocultar todos
        Object.values(bloques).forEach(id => {
            const bloque = document.getElementById(id);
            if (!bloque) return;

            bloque.classList.add('d-none');
            bloque.querySelectorAll('input, select').forEach(el => el.disabled = true);
        });

        // Mostrar el bloque correspondiente
        if (bloques[estado]) {
            const bloque = document.getElementById(bloques[estado]);
            bloque.classList.remove('d-none');
            bloque.querySelectorAll('input, select').forEach(el => el.disabled = false);
        }
    });
</script>


@include('boleta_mensual._modal_estado_financiero')


@endsection
