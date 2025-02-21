@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Salud</h1>

    <form action="{{ route('saluds.store') }}" method="POST">
        @csrf
        @include('saluds.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection