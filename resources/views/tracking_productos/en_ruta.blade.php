@extends('layouts.app')

@section('content')

<div class="container">
    <h2>Escaneo - En Ruta</h2>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-5">
                <label for="razon_social" class="form-label">Filtrar por Razón Social</label>
                <select name="razon_social" id="razon_social" class="form-select" onchange="this.form.submit()">
                    <option value="">— Ver todas —</option>
                    @foreach ($razonesSociales as $razon)
                        <option value="{{ $razon }}" {{ $razon == request('razon_social') ? 'selected' : '' }}>
                            {{ $razon }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="{{ route('tracking_productos.en_ruta') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </form>



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
                    <form method="POST" action="{{ route('tracking_productos.agregar_codigo_ruta') }}" onsubmit="this.querySelector('button').disabled = true;">
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

        {{-- Información del bulto escaneado --}}
        @php $bulto = session('ultimo_bulto'); @endphp

    </div>

    {{-- Códigos escaneados actualmente --}}
    @if (count($escaneados))
        <div class="card mb-4">
            <div class="card-header">Códigos escaneados para En Ruta</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover m-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($escaneados as $index => $codigo)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $codigo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('tracking_productos.guardar_ruta') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Registrar En Ruta</button>
        </form>
    @endif

    {{-- Productos en estado Recepcionado sin marcar En Ruta --}}
    <h4 class="mt-5">Productos actualmente en estado Recepcionado (sin marcar En Ruta):</h4>
    <ul class="list-group mb-5">
        @forelse ($pendientes as $item)
            <li class="list-group-item">
                <strong>{{ $item['codigo'] }}</strong> — {{ $item['nombre'] }} |
                Peso: {{ $item['peso'] }}, Dirección: {{ $item['direccion'] }}<br>
                <small><strong>Recepcionado por:</strong> {{ $item['usuario'] }}</small>
            </li>
        @empty
            <li class="list-group-item">No hay productos pendientes.</li>
        @endforelse
    </ul>
</div>

{{-- Autofocus después de recarga --}}
<script>
    window.onload = function () {
        const input = document.getElementById('codigo');
        if (input) {
            input.focus();
            input.select();
        }
    };
</script>
@endsection
