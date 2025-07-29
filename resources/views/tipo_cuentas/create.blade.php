@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Tipo Cuenta</h1>

    <form action="{{ route('tipo_cuentas.store') }}" method="POST">
        @csrf
        @include('tipo_cuentas.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
