@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Nueva Compra</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('compras.store') }}" method="POST">
        @csrf

        @include('compras.form', [
            'action' => route('compras.store'),
            'method' => 'POST',
            'compra' => new \App\Models\Compra(),
            'proveedores' => $proveedores,
            'empresas' => $empresas,
            'centrosCosto' => $centrosCosto,
            'tiposPagos' => $tiposPagos,
        ])

        <button type="submit" class="btn btn-success">Guardar Compra</button>
    </form>

    <a href="{{ route('compras.index') }}" class="btn btn-secondary mt-2">
        ← Regresar
    </a>
</div>
@endsection
