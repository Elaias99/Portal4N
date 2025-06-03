@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Escaneo de Productos — Retirado</h2>

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
                    <form method="POST" action="{{ route('tracking_productos.agregar_codigo') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Escanea un código</label>
                            <input type="text" name="codigo" class="form-control" id="codigo" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Información del producto escaneado --}}
        @if(session('ultimo_producto_base'))
        @php
            $p = session('ultimo_producto_base');
        @endphp
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Información del producto escaneado</div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> {{ $p->nombre }}</p>
                    <p><strong>Peso:</strong> {{ $p->peso }}</p>
                    <p><strong>Altura:</strong> {{ $p->altura }}</p>
                    <p><strong>Ancho:</strong> {{ $p->ancho }}</p>
                    <p><strong>Profundidad:</strong> {{ $p->profundidad }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Códigos escaneados --}}
    @php
        $codigos = session('codigos_retiro', []);
        $productos = \App\Models\ProductoBase::whereIn('codigo', $codigos)->get()->keyBy('codigo');
    @endphp

    @if (count($codigos))
        <div class="card">
            <div class="card-header">Códigos Escaneados</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover m-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($codigos as $index => $codigo)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $codigo }}</td>
                                <td>Retirado</td>
                                <td>{{ now()->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Registrar --}}
        <form method="POST" action="{{ route('tracking_productos.guardar_lote') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-primary">Registrar Retiro</button>
        </form>
    @endif
</div>
@endsection
