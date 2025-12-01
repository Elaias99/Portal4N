@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 fade-in">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-semibold text-dark mb-0">Historial de Movimientos — Compras</h4>
        <a href="{{ route('finanzas_compras.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>

    {{-- ====== FILTROS ====== --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px;">
        <div class="card-body">
            <form method="GET" action="{{ route('panelfinanza.show_compras') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Fecha ingreso estado (desde)
                        </label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                            value="{{ request('fecha_inicio') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">
                            Fecha ingreso estado (hasta)
                        </label>
                        <input type="date" name="fecha_fin" class="form-control form-control-sm"
                            value="{{ request('fecha_fin') }}">
                    </div>


                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Empresa</label>
                        <select name="empresa_id" class="form-select form-select-sm">
                            <option value="">-- Todas --</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-dark btn-sm flex-fill">Buscar</button>
                        <a href="{{ route('panelfinanza.show_compras') }}" class="btn btn-outline-dark btn-sm flex-fill">Limpiar</a>
                        <a href="{{ route('panelfinanza.export_compras', request()->query()) }}" class="btn btn-success btn-sm flex-fill">
                            Exportar
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- ====== TABLA ====== --}}
    <div class="card border-0 shadow-sm" style="border-radius: 10px;">
        <div class="card-body table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background-color: #f8f9fa;">
                    <tr class="text-muted small">
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Proveedor</th>
                        <th>Folio</th>
                        <th>Fecha ingreso estado</th>

                        <th>Estado anterior → nuevo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>



                @forelse($movimientos as $mov)


                    @php
                        $fechaEstado = null;

                        // Timestamp exacto del movimiento
                        $ts = optional($mov->created_at)->toDateTimeString();

                        // Buscar abono creado en ese momento
                        $abono = $mov->compra->abonos()
                            ->where('created_at', $ts)
                            ->first();

                        if ($abono) {
                            $fechaEstado = $abono->fecha_abono;
                        }

                        // Buscar pago
                        $pago = $mov->compra->pagos()
                            ->where('created_at', $ts)
                            ->first();

                        if ($pago) {
                            $fechaEstado = $pago->fecha_pago;
                        }

                        // Buscar cruce
                        $cruce = $mov->compra->cruces()
                            ->where('created_at', $ts)
                            ->first();

                        if ($cruce) {
                            $fechaEstado = $cruce->fecha_cruce;
                        }

                        // Buscar pronto pago
                        $pp = $mov->compra->prontoPagos()
                            ->where('created_at', $ts)
                            ->first();

                        if ($pp) {
                            $fechaEstado = $pp->fecha_pronto_pago;
                        }
                    @endphp


                    <tr>
                        <td>{{ $mov->created_at?->format('d-m-Y H:i') ?? '—' }}</td>
                        <td>{{ $mov->compra?->empresa?->Nombre ?? '—' }}</td>
                        <td>{{ $mov->compra?->razon_social ?? '—' }}</td>
                        <td>{{ $mov->compra?->folio ?? '—' }}</td>

                        <td>
                            @if($fechaEstado)
                                {{ \Carbon\Carbon::parse($fechaEstado)->format('d-m-Y') }}
                            @else
                                —
                            @endif
                        </td>


                        {{-- ESTADO --}}
                        <td>
                            @php
                                $anterior = $mov->estado_anterior;
                                $nuevo = $mov->nuevo_estado;
                            @endphp

                            @if(is_null($anterior) && is_null($nuevo))
                                <span class="text-danger fw-semibold">Eliminación de pago</span>
                            @elseif($anterior === $nuevo && $nuevo === 'Pago')
                                <span class="text-success fw-semibold">Pago registrado</span>
                            @elseif($anterior === $nuevo)
                                <span class="text-muted">Sin cambio ({{ $nuevo }})</span>
                            @else
                                {{ $anterior ?? '—' }} <span class="text-muted">→</span> {{ $nuevo ?? '—' }}
                            @endif
                        </td>

                        <td>{{ $mov->user?->name ?? '—' }}</td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Sin movimientos registrados</td>
                    </tr>
                @endforelse





                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $movimientos->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>

</div>

<style>
.table tbody tr:hover {
    background-color: #f3f4f6;
    transform: scale(1.002);
    transition: all 0.2s ease;
}
</style>
@endsection
