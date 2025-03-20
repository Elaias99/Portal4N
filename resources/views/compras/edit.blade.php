@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Compra</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('compras.update', $compra->id) }}" method="POST">
        @csrf
        @method('PUT')

        @include('compras.form', [
            'action' => route('compras.update', $compra->id),
            'method' => 'PUT',
            'compra' => $compra,
            'proveedores' => $proveedores,
            'empresas' => $empresas,
            'centrosCosto' => $centrosCosto,
            'tiposPagos' => $tiposPagos,
        ])

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>

    <a href="{{ route('compras.index') }}" class="btn btn-secondary mt-2">
        ← Regresar
    </a>
</div>
@endsection
