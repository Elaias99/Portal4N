@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 fade-in">

    {{-- ====== ENCABEZADO ====== --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-semibold text-dark mb-0">Historial de Movimientos</h4>
        <a href="{{ route('cobranzas.documentos') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>

    {{-- ====== FILTROS ====== --}}
    <div class="card border-0 shadow-sm mb-3 fade-in" style="border-radius: 10px;">
        <div class="card-body">
            <form method="GET" action="{{ route('panelfinanza.show') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                               value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Hasta</label>
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
                        <label class="form-label small text-muted mb-1">Razón Social</label>
                        <input type="text" name="razon_social" class="form-control form-control-sm"
                               placeholder="Buscar cliente..." value="{{ request('razon_social') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-dark btn-sm flex-fill">Buscar</button>
                        <a href="{{ route('panelfinanza.show') }}" class="btn btn-outline-dark btn-sm flex-fill">Limpiar</a>
                        <a href="{{ route('panelfinanza.export', request()->query()) }}" class="btn btn-success btn-sm flex-fill">Exportar</a>
                    </div>
                </div>
            </form>

            {{-- Total mostrado destacado --}}
            @if(isset($totalMontos))
                <div class="mt-4 text-end fade-in-delayed">
                    <div class="total-box d-inline-block px-4 py-2">
                        <span class="text-muted small d-block">Total mostrado</span>
                        <span class="fw-bold total-amount">${{ number_format($totalMontos, 0, ',', '.') }}</span>
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
                        <th>Fecha</th>
                        <th>Tipo Movimiento</th>
                        <th>Documento</th>
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Monto Total</th>
                        <th>Saldo Pendiente</th>
                        <th>Usuario</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                        <tr>
                            <td>{{ optional($mov->created_at)->format('d-m-Y H:i') }}</td>


                            <td>
                                @php
                                    $tipo = strtolower($mov->tipo_movimiento ?? '');
                                @endphp

                                @if(Str::contains($tipo, 'reprocesamiento'))
                                    <span class="badge bg-info text-dark" 
                                        data-bs-toggle="tooltip"
                                        title="El sistema realizó este movimiento automáticamente para mantener la información actualizada. No fue hecho por un usuario.">
                                        <i class="bi bi-gear-fill me-1"></i> Automático
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst($mov->tipo_movimiento) }}
                                    </span>
                                @endif
                            </td>




                            <td>
                                @if($mov->documento)
                                    Folio: <strong>{{ $mov->documento->folio }}</strong><br>
                                    <small class="text-muted">{{ $mov->documento->tipoDocumento->nombre ?? '—' }}</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $mov->documento->razon_social ?? '—' }}</td>
                            <td>{{ $mov->documento?->empresa?->Nombre ?? 'Sin empresa' }}</td>
                            <td>${{ number_format($mov->documento->monto_total ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @if(isset($mov->documento->saldo_pendiente))
                                    ${{ number_format($mov->documento->saldo_pendiente, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $mov->user->name ?? '—' }}</td>
                            <td>{{ $mov->descripcion ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Sin movimientos registrados</td>
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
.total-amount { font-size: 1.25rem; color: #212529; letter-spacing: 0.5px; }
.fade-in { opacity: 0; transform: translateY(10px); animation: fadeInUp 0.5s ease-out forwards; }
.fade-in-delayed { opacity: 0; transform: translateY(5px); animation: fadeInUp 0.8s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px);} to {opacity:1; transform:translateY(0);} }
</style>


{{-- ====== TOOLTIP INICIALIZACIÓN ====== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
});
</script>

@endsection