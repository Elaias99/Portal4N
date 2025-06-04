@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Asignar productos escaneados a un repartidor</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Formulario de escaneo --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('tracking_productos.agregar_codigo_asignacion') }}" onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Escanear código</label>
                            <input type="text" name="codigo" id="codigo" class="form-control" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Agregar código</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Información del último bulto escaneado --}}
        @if(session('ultimo_bulto'))
            @php $b = session('ultimo_bulto'); @endphp
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Último bulto escaneado</div>
                    <div class="card-body">
                        <p><strong>Descripción:</strong> {{ $b->descripcion_bulto ?? '—' }}</p>
                        <p><strong>Peso:</strong> {{ $b->peso ?? '—' }}</p>
                        <p><strong>Dirección:</strong> {{ $b->direccion ?? '—' }}</p>
                        <p><strong>Referencia:</strong> {{ $b->referencia ?? '—' }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Listado de códigos escaneados --}}
    @php
        $codigos = session('codigos_asignacion', []);
        $productos = \App\Models\Bultos::whereIn('codigo_bulto', $codigos)->get()->keyBy('codigo_bulto');
    @endphp

    @if (count($codigos))
        <div class="card mb-3">
            <div class="card-header">Códigos escaneados</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover m-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Peso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($codigos as $i => $codigo)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $codigo }}</td>
                                <td>{{ $productos[$codigo]->descripcion_bulto ?? '—' }}</td>
                                <td>{{ $productos[$codigo]->peso ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Asignar productos a chofer --}}
        <form method="POST" action="{{ route('tracking_productos.asignar_seleccionados') }}" onsubmit="this.querySelector('button').disabled = true;">
            @csrf
            <div class="mb-3">
                <label for="chofer_id" class="form-label">Seleccionar chofer:</label>
                <select name="chofer_id" id="chofer_id" class="form-select" required>
                    <option value="">-- Selecciona un chofer --</option>
                    @foreach ($choferes as $chofer)
                        <option value="{{ $chofer->id }}">{{ $chofer->Nombre }} {{ $chofer->ApellidoPaterno }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Asignar productos escaneados</button>
        </form>
    @endif
</div>

{{-- Autofocus al recargar --}}
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
