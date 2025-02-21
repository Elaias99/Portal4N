@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Cargo</h1>
    <form action="{{ route('cargos.update', $cargo->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('cargos.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
