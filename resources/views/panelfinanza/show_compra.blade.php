@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 fade-in">

    {{-- ====== ENCABEZADO ====== --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-semibold text-dark mb-0">Historial de Movimientos — Compras</h4>
        <a href="{{ route('finanzas_compras.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>

    {{-- ====== FILTROS ====== --}}
    <div class="card border-0 shadow-sm mb-3 fade-in" style="border-radius: 10px;">
        <div class="card-body">
            <form method="GET" action="{{ route('panelfinanza.show_compras') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Fecha movimiento (desde)</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                            value="{{ request('fecha_inicio') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Fecha movimiento (hasta)</label>
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

                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Proveedor</label>
                        <input type="text" name="razon_social" class="form-control form-control-sm"
                               placeholder="Buscar proveedor..." value="{{ request('razon_social') }}">
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

            {{-- Total mostrado destacado --}}
            @if(isset($totalMontos))
                @php
                    $colorTotal = $totalMontos >= 0 ? 'text-success' : 'text-danger';
                    $signoTotal = $totalMontos >= 0 ? '+' : '–';
                @endphp
                <div class="mt-4 text-end fade-in-delayed">
                    <div class="total-box d-inline-block px-4 py-2">
                        <span class="text-muted small d-block">Total mostrado (flujo neto)</span>
                        <span class="fw-bold total-amount {{ $colorTotal }}">
                            {{ $signoTotal }}${{ number_format(abs($totalMontos), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ====== TABLA ====== --}}
    <div class="card border-0 shadow-sm fade-in" style="border-radius: 10px;">
        <div class="card-body table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background-color: #f8f9fa;">
                    <tr class="text-muted small">
                        <th>Fecha evento</th>
                        <th>Tipo Movimiento</th>
                        <th>Documento</th>
                        <th>Proveedor</th>
                        <th>Empresa</th>
                        <th>Monto Movimiento</th>
                        <th>Usuario</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                        @php
                            $tipo = strtolower($mov->tipo_movimiento ?? '');
                            $monto = 0;
                            $signo = '+';
                            $color = 'text-success';
                            $fechaEvento = null;

                            // === Determinar monto según tipo ===
                            if (Str::contains($tipo, 'abono') || Str::contains($tipo, 'cruce')) {
                                $monto = $mov->datos_nuevos['monto']
                                    ?? $mov->datos_anteriores['monto']
                                    ?? 0;
                            } elseif (Str::contains($tipo, 'pago') || Str::contains($tipo, 'pronto pago')) {
                                $monto = $mov->compra->monto_total ?? 0;
                            }

                            // === Ajuste por eliminación ===
                            if (Str::contains($tipo, 'eliminación')) {
                                $monto *= -1;
                                $signo = '–';
                                $color = 'text-danger';
                            }

                            // === Fecha del evento real ===
                            if (Str::contains($tipo, 'abono')) {
                                $fechaEvento = $mov->datos_nuevos['fecha_abono']
                                    ?? $mov->datos_anteriores['fecha_abono']
                                    ?? null;
                            } elseif (Str::contains($tipo, 'cruce')) {
                                $fechaEvento = $mov->datos_nuevos['fecha_cruce']
                                    ?? $mov->datos_anteriores['fecha_cruce']
                                    ?? null;
                            } elseif (Str::contains($tipo, 'pago')) {
                                $fechaEvento = $mov->datos_nuevos['fecha_pago']
                                    ?? $mov->datos_anteriores['fecha_pago']
                                    ?? null;
                            } elseif (Str::contains(Str::ascii($tipo), 'pronto pago')) {
                                $fechaEvento = $mov->datos_nuevos['fecha_pronto_pago']
                                    ?? $mov->datos_anteriores['fecha_pronto_pago']
                                    ?? null;
                            }

                            // Si no hay fecha de evento, usar fecha_cambio como fallback
                            $fechaMostrar = $fechaEvento ?? $mov->fecha_cambio ?? $mov->created_at;
                        @endphp

                        <tr>
                            {{-- Fecha --}}
                            <td>{{ $fechaMostrar ? \Carbon\Carbon::parse($fechaMostrar)->format('d-m-Y') : '—' }}</td>

                            {{-- Tipo movimiento --}}
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ ucfirst($mov->tipo_movimiento) }}
                                </span>
                            </td>

                            {{-- Documento --}}
                            <td>
                                @if($mov->compra)
                                    Folio: <strong>{{ $mov->compra->folio }}</strong><br>
                                    <small class="text-muted">{{ $mov->compra->tipoDocumento->nombre ?? '—' }}</small>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Proveedor --}}
                            <td>{{ $mov->compra->razon_social ?? '—' }}</td>

                            {{-- Empresa --}}
                            <td>{{ $mov->compra?->empresa?->Nombre ?? 'Sin empresa' }}</td>

                            {{-- Monto Movimiento --}}
                            <td class="fw-semibold {{ $color }}">
                                {{ $signo }}${{ number_format(abs($monto), 0, ',', '.') }}
                            </td>

                            {{-- Usuario --}}
                            <td>{{ $mov->user?->name ?? '—' }}</td>

                            {{-- Descripción --}}
                            <td style="max-width: 300px; white-space: pre-line;">
                                {{ $mov->descripcion ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Sin movimientos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>

    {{-- ====== PAGINACIÓN ====== --}}
    <div class="mt-3 d-flex justify-content-center fade-in-delayed">
        {{ $movimientos->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>

</div>

{{-- ====== ESTILOS ====== --}}
<style>
.table tbody tr { transition: background-color 0.25s ease, transform 0.2s ease; }
.table tbody tr:hover { background-color: #f3f4f6; transform: scale(1.002); }
.total-box {
    background: #f1f3f5; border-radius: 8px;
    border: 1px solid #e2e3e5; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.total-amount { font-size: 1.25rem; letter-spacing: 0.5px; }
.text-success { color: #198754 !important; }
.text-danger { color: #dc3545 !important; }
.fade-in { opacity: 0; transform: translateY(10px); animation: fadeInUp 0.5s ease-out forwards; }
.fade-in-delayed { opacity: 0; transform: translateY(5px); animation: fadeInUp 0.8s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px);} to {opacity:1; transform:translateY(0);} }
</style>

{{-- ====== TOOLTIP ====== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
});
</script>

@endsection
