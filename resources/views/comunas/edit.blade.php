@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Comuna</h1>
    <form action="{{ route('comunas.update', $comuna->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('comunas.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection

