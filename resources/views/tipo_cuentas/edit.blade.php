@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Tipo Cuenta</h1>
    <form action="{{ route('tipo_cuentas.update', $tipo_cuenta->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('tipo_cuentas.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
