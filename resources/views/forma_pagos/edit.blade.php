@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Forma Pago</h1>
    <form action="{{ route('forma_pagos.update', $forma_pago->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('forma_pagos.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
