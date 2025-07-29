@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Tipo Documento</h1>

    <form action="{{ route('tipo_documentos.store') }}" method="POST">
        @csrf
        @include('tipo_documentos.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
