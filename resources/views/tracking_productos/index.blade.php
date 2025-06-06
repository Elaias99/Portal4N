@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h2 class="text-center mb-4 font-weight-bold">Escaneo de Productos</h2>

    @if (session('success'))
        <div class="alert alert-success text-center">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('tracking.dashboard') }}" class="btn btn-outline-primary d-inline-flex align-items-center">
            <i class="fas fa-chart-line mr-2"></i> Ver Panel de Estado
        </a>
    </div>

    <div class="row justify-content-center">

        @if($areaId == 5)
            {{-- Tarjeta 1 --}}
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <div class="card shadow-sm border-0 text-center h-100">
                    <div class="card-body">
                        <div class="text-primary mb-2 display-4"><i class="fas fa-truck"></i></div>
                        <h5 class="card-title">Opción de Chofer</h5>
                        <p class="text-muted small mb-4">Para retiro</p>
                        <a href="{{ route('tracking_productos.retiro') }}" class="btn btn-primary w-100">Ingresar</a>
                    </div>
                </div>
            </div>

            {{-- Tarjeta 3 --}}
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <div class="card shadow-sm border-0 text-center h-100">
                    <div class="card-body">
                        <div class="text-success mb-2 display-4"><i class="fas fa-route"></i></div>
                        <h5 class="card-title">Opción Chofer</h5>
                        <p class="text-muted small mb-4">Para En Ruta</p>
                        <a href="{{ route('tracking_productos.en_ruta') }}" class="btn btn-primary w-100">Ingresar</a>
                    </div>
                </div>
            </div>
        @endif

        @if($areaId == 1)
            {{-- Tarjeta 2 --}}
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <div class="card shadow-sm border-0 text-center h-100">
                    <div class="card-body">
                        <div class="text-info mb-2 display-4"><i class="fas fa-warehouse"></i></div>
                        <h5 class="card-title">Operaciones</h5>
                        <p class="text-muted small mb-4">Para 2do Pisoleo</p>
                        <a href="{{ route('tracking_productos.recepcion') }}" class="btn btn-primary w-100">Ingresar</a>
                    </div>
                </div>
            </div>

            {{-- Tarjeta 3 --}}
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <div class="card shadow-sm border-0 text-center h-100">
                    <div class="card-body">
                        <div class="text-warning mb-2 display-4"><i class="fas fa-tasks"></i></div>
                        <h5 class="card-title">Asignar Productos</h5>
                        <p class="text-muted small mb-4">A Chofer</p>
                        <a href="{{ route('tracking_productos.asignar_individual') }}" class="btn btn-primary w-100">Ingresar</a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tarjetas inactivas --}}
        @for ($i = 4; $i <= 6; $i++)
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <div class="card shadow-sm border-0 text-center h-100 bg-light">
                    <div class="card-body opacity-75">
                        <div class="text-muted mb-2 display-4"><i class="fas fa-ban"></i></div>
                        <h5 class="card-title text-muted">Opción {{ $i }}</h5>
                        <p class="text-muted small mb-4">No disponible actualmente</p>
                        <button class="btn btn-secondary w-100" disabled title="Esta función no está habilitada aún">
                            No disponible
                        </button>
                    </div>
                </div>
            </div>
        @endfor

    </div>
</div>
@endsection
