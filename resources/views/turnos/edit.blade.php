@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Turno</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('turnos.update', $turno->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('turnos.form')
        <button type="submit" class="btn btn-success">Actualizar</button>
    </form>
</div>
@endsection
