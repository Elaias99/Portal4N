@extends('layouts.app')

@section('content')

<div class="container">
    <h1>Crear Comuna</h1>

    <form action="{{ route('comunas.store') }}" method="POST">
        @csrf
        @include('comunas.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
