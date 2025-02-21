@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Compra</h1>

    


    @include('compras.form', [
        'action' => route('compras.update', $compra->id),
        'method' => 'PUT',
        'compra' => $compra,
        'proveedores' => $proveedores,
    ])

    <!-- Botón para regresar atrás -->
    <a href="{{ route('compras.index') }}" class="btn btn-primary">
        ← Regresar
    </a>
    
</div>


@endsection
