@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Empresa</h1>

    <form action="{{ route('empresas.store') }}" method="POST" enctype="multipart/form-data"> <!-- AÑADE ENCTYPE -->
        @csrf
        @include('empresas.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('empresas.index') }}" class="btn btn-primary">Atrás</a>
    </form>
    
</div>

@endsection
