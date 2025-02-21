@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Situación</h1>

    <form action="{{ route('situacions.store') }}" method="POST">
        @csrf
        @include('situacions.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
