@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Tipo Documento</h1>
    <form action="{{ route('tipo_documentos.update', $tipo_documento->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('tipo_documentos.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
