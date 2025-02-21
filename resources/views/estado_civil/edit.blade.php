<!-- resources/views/estado_civil/edit.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Estado Civil</h1>
    <form action="{{ route('estado_civil.update', $estadoCivil->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('estado_civil.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
