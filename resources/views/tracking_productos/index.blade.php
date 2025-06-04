@extends('layouts.app') {{-- Asegúrate de que exista este layout base --}}

@section('content')
<div class="container text-center">
    <h2>Escaneo Productos</h2>



    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('tracking.dashboard') }}" class="btn btn-info mb-3">
        Ver Panel de Estado
    </a>



    <div class="row mt-4">
        {{-- Opción 1: Chofer - Retiro --}}
        @if($areaId == 5) {{-- Área 5: Choferes --}}
            <div class="col-md-4 mb-3">
                <a href="{{ route('tracking_productos.retiro') }}" class="btn btn-primary btn-lg w-100 h-100">
                    1<br>Opción de chofer<br>para retiro
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('tracking_productos.en_ruta') }}" class="btn btn-warning btn-lg w-100 h-100">
                    3<br>Opción chofer<br>para En Ruta
                </a>
            </div>


        @endif

        {{-- Opción 2: Operaciones - 2do Pisoleo --}}
        @if($areaId == 1)
            <div class="col-md-4 mb-3">
                <a href="{{ route('tracking_productos.recepcion') }}" class="btn btn-success btn-lg w-100 h-100">
                    2<br>Opción operaciones<br>para 2do pisoleo
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('tracking_productos.asignar_individual') }}" class="btn btn-warning btn-lg w-100 h-100">
                    3<br>Asignar<br>productos a chofer
                </a>
            </div>
        @endif



        {{-- Espacios para futuras opciones --}}
        <div class="col-md-4 mb-3"><a href="#" class="btn btn-secondary btn-lg w-100 h-100">3<br>opción</a></div>
        <div class="col-md-4 mb-3"><a href="#" class="btn btn-secondary btn-lg w-100 h-100">4<br>opción</a></div>
        <div class="col-md-4 mb-3"><a href="#" class="btn btn-secondary btn-lg w-100 h-100">5<br>opción</a></div>
        <div class="col-md-4 mb-3"><a href="#" class="btn btn-secondary btn-lg w-100 h-100">6<br>opción</a></div>
    </div>
</div>
@endsection
