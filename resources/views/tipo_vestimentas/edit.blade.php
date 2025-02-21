@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Tipo de Vestimenta</h1>
    <form action="{{ route('tipo_vestimentas.update', $tipoVestimenta->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('tipo_vestimentas.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
