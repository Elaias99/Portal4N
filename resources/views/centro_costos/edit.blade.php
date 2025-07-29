@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Centro Costo</h1>
    <form action="{{ route('centro_costos.update', $centro_costo->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('centro_costos.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
