@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Cargo</h1>

    <form action="{{ route('cargos.store') }}" method="POST">
        @csrf
        @include('cargos.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection