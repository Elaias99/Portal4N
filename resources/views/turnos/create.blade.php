@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Nuevo Turno</h1>
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('turnos.store') }}" method="POST">
        @csrf
        @include('turnos.form')
        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
@endsection
