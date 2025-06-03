@extends('layouts.app')

@section('content')




<div class="container">
    <h2>Escaneo - En Ruta</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row mb-4">
        {{-- Escaneo --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('tracking_productos.agregar_codigo_ruta') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Escanea un código</label>
                            <input type="text" name="codigo" class="form-control" id="codigo" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">Agregar a la lista</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Información del producto escaneado --}}
        @php
            $producto = session('ultimo_producto_base');
        @endphp

        @if ($producto)
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Información del producto escaneado</div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> {{ $producto->nombre }}</p>
                        <p><strong>Peso:</strong> {{ $producto->peso }}</p>
                        <p><strong>Altura:</strong> {{ $producto->altura }}</p>
                        <p><strong>Ancho:</strong> {{ $producto->ancho }}</p>
                        <p><strong>Profundidad:</strong> {{ $producto->profundidad }}</p>
                    </div>
                </div>
            </div>
            @endif
    </div>

    {{-- Códigos escaneados actualmente --}}
    @if (count($escaneados))
        <h4 class="mt-4">Códigos escaneados para En Ruta:</h4>
        <ul class="list-group mb-3">
            @foreach ($escaneados as $codigo)
                <li class="list-group-item">{{ $codigo }}</li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('tracking_productos.guardar_ruta') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Registrar En Ruta</button>
        </form>
    @endif

    {{-- Productos en estado Recepcionado sin marcar En Ruta --}}
    <h4 class="mt-5">Productos actualmente en estado Recepcionado (sin marcar En Ruta):</h4>
    <ul class="list-group">


        @forelse ($pendientes as $item)
            <li class="list-group-item">
                <strong>{{ $item['codigo'] }}</strong> — {{ $item['nombre'] }} |
                Peso: {{ $item['peso'] }}, Dimensiones: {{ $item['dimensiones'] }}<br>
                <small><strong>Recepcionado por:</strong> {{ $item['usuario'] }}</small>
            </li>
        @empty




            <li class="list-group-item">No hay productos pendientes.</li>
        @endforelse
    </ul>

</div>

@endsection
