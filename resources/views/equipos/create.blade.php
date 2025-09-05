@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Agregar Equipo</h1>

    <form action="{{ route('equipos.store') }}" method="POST">
        @csrf
        @include('equipos.form', ['equipo' => new App\Models\Equipo])
        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="{{ route('equipos.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
