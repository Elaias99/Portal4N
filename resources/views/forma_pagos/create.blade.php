@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Forma Pago</h1>

    <form action="{{ route('forma_pagos.store') }}" method="POST">
        @csrf
        @include('forma_pagos.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
