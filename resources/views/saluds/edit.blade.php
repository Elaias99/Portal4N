@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Salud</h1>
    <form action="{{ route('saluds.update', $salud->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('saluds.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection