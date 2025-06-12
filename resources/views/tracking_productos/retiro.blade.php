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
                    <form method="POST" action="{{ route('tracking_productos.agregar_codigo') }}" onsubmit="this.querySelector('button').disabled = true;">
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

    </div>

    {{-- Códigos escaneados --}}
    @php $codigos = session('codigos_retiro', []); @endphp

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
                                <td>{{ is_array($codigo) ? $codigo['codigo'] : $codigo }}</td>
                                <td>Retirado</td>
                                <td>{{ is_array($codigo) ? $codigo['timestamp'] : now()->format('Y-m-d H:i') }}</td>
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

{{-- Reforzar autofocus después de recarga --}}
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
