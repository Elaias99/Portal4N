@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Editar Cobranza de Compras</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('cobranzas-compras.update', $cobranzaCompra) }}" method="POST">
                @method('PUT')
                @include('cobranzas_compras.form', ['btnText' => 'Actualizar'])
            </form>
        </div>
    </div>
</div>
@endsection
