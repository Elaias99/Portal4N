@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Centro Costo</h1>

    <form action="{{ route('centro_costos.store') }}" method="POST">
        @csrf
        @include('centro_costos.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
