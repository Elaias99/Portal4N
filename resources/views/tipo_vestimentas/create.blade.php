@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Tipo de Vestimenta</h1>
    <form action="{{ route('tipo_vestimentas.store') }}" method="POST">
        @csrf
        @include('tipo_vestimentas.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ url('tipo_vestimentas') }}" class="btn btn-secondary">Atrás</a>
    </form>
</div>
@endsection
