@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar AFP</h1>
    <form action="{{ route('situacions.update', $situacion->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('situacions.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
