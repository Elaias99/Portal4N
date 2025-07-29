@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Banco</h1>

    <form action="{{ route('bancos.store') }}" method="POST">
        @csrf
        @include('bancos.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
