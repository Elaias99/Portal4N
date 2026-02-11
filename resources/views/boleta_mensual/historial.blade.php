@extends('layouts.app')

@section('content')
<div class="container">

    {{-- ========================= --}}
    {{-- ENCABEZADO --}}
    {{-- ========================= --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Historial de movimientos de honorarios</h4>

        <div class="text-end">
            <a href="{{ route('movimientos.honorarios.export') }}"
            class="btn btn-success">
                Exportar a Excel
            </a>
        </div>
    </div>


    {{-- ========================= --}}
    {{-- FILTRO HORIZONTAL COMPLETO --}}
    {{-- ========================= --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form method="GET"
                action="{{ route('movimientos.honorarios.historial') }}">

                <div class="row g-3 align-items-end">

                    <div class="col-lg-2">
                        <label class="form-label">Empresa</label>
                        <select name="empresa_id"
                                class="form-select form-select-sm">
                            <option value="">Todas</option>

                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}"
                                    {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="col-lg-2">
                        <label class="form-label">Usuario</label>
                        <input type="text"
                            name="usuario"
                            value="{{ request('usuario') }}"
                            class="form-control form-control-sm"
                            placeholder="Nombre usuario">
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label">Tipo movimiento</label>
                        <select name="tipo"
                                class="form-select form-select-sm">
                            <option value="">Todos</option>

                            <option value="Registro de abono"
                                {{ request('tipo') == 'Registro de abono' ? 'selected' : '' }}>
                                Abono
                            </option>

                            <option value="Eliminación de abono"
                                {{ request('tipo') == 'Eliminación de abono' ? 'selected' : '' }}>
                                Eliminación Abono
                            </option>

                            <option value="Pago"
                                {{ request('tipo') == 'Pago' ? 'selected' : '' }}>
                                Pago
                            </option>

                            <option value="Pago masivo con exportación"
                                {{ request('tipo') == 'Pago masivo con exportación' ? 'selected' : '' }}>
                                Pago Masivo
                            </option>

                            <option value="Cruce"
                                {{ request('tipo') == 'Cruce' ? 'selected' : '' }}>
                                Cruce
                            </option>
                        </select>
                    </div>


                    {{-- <div class="col-lg-2">
                        <label class="form-label">Desde</label>
                        <input type="date"
                            name="fecha_desde"
                            value="{{ request('fecha_desde') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label">Hasta</label>
                        <input type="date"
                            name="fecha_hasta"
                            value="{{ request('fecha_hasta') }}"
                            class="form-control form-control-sm">
                    </div> --}}

                    <div class="col-lg-1">
                        <button type="submit"
                                class="btn btn-primary btn-sm w-100">
                            Buscar
                        </button>
                    </div>

                    <div class="col-lg-1">
                        <a href="{{ route('movimientos.honorarios.historial') }}"
                        class="btn btn-outline-secondary btn-sm w-100">
                            Limpiar
                        </a>
                    </div>


                </div>

            </form>

        </div>
    </div>




    {{-- =========================
    TABLA DE MOVIMIENTOS
    ========================== --}}
    <div class="card">
        <div class="card-header">
            <strong>Libro de movimientos</strong>
        </div>

        <div class="card-body p-0">

            @if($movimientos->isEmpty())
                <div class="p-4 text-muted">
                    No existen movimientos registrados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Empresa</th>
                                <th>Emisor</th>
                                <th>Folio</th>
                                <th>Movimiento</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($movimientos as $mov)
                                @php
                                    $hon = $mov->honorario;
                                @endphp

                                <tr>
                                    {{-- Fecha --}}
                                    <td>
                                        {{ $mov->fecha_cambio?->format('d-m-Y H:i') }}
                                    </td>

                                    {{-- Usuario --}}
                                    <td>
                                        {{ $mov->user->name ?? 'Sistema' }}
                                    </td>

                                    {{-- Empresa --}}
                                    <td>
                                        {{ $hon?->empresa?->Nombre ?? '-' }}
                                    </td>

                                    {{-- Emisor --}}
                                    <td>
                                        {{ $hon?->razon_social_emisor ?? '-' }}
                                    </td>

                                    {{-- Folio --}}
                                    <td class="text-center fw-bold">
                                        {{ $hon?->folio ?? '-' }}
                                    </td>

                                    {{-- Tipo movimiento --}}
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $mov->tipo_movimiento }}
                                        </span>
                                    </td>

                                    {{-- Estado --}}
                                    <td>
                                        {{ $mov->estado_anterior ?? '-' }}
                                        →
                                        <strong>{{ $mov->nuevo_estado ?? '-' }}</strong>
                                    </td>

                                    {{-- Detalle --}}
                                    <td>
                                        <div>{{ $mov->descripcion }}</div>

                                        @if($mov->datos_anteriores || $mov->datos_nuevos)
                                            <small class="text-muted d-block mt-1">
                                                @if(isset($mov->datos_anteriores['saldo']))
                                                    Saldo anterior:
                                                    ${{ number_format($mov->datos_anteriores['saldo'], 0, ',', '.') }}
                                                    →
                                                @endif

                                                @if(isset($mov->datos_nuevos['saldo']))
                                                    Saldo nuevo:
                                                    ${{ number_format($mov->datos_nuevos['saldo'], 0, ',', '.') }}
                                                @endif
                                            </small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>

        {{-- PAGINACIÓN --}}
        @if($movimientos->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $movimientos->links('pagination::bootstrap-4') }}
            </div>
        @endif
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
@endsection
