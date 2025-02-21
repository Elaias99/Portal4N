@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Nueva Compra</h1>
    @include('compras.form', [
        'action' => route('compras.store'),
        'method' => 'POST',
        'compra' => new \App\Models\Compra(),
        'proveedores' => $proveedores,
    ])

    <a href="{{ route('compras.index') }}" class="btn btn-primary">
        ← Regresar
    </a>
</div>
@endsection
