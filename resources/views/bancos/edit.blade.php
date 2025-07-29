@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Banco</h1>
    <form action="{{ route('bancos.update', $banco->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('bancos.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
