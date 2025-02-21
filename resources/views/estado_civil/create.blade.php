<!-- resources/views/estado_civil/create.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Agregar Estado Civil</h1>
    <form action="{{ route('estado_civil.store') }}" method="POST">
        @csrf
        @include('estado_civil.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
@endsection
