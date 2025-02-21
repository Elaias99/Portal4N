@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Empresa</h1>
    <form action="{{ route('empresas.update', $empresa->id) }}" method="POST" enctype="multipart/form-data"> <!-- AÑADE ENCTYPE -->
        @csrf
        @method('PUT')
        @include('empresas.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
