@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Hijo</h1>
    <form action="{{ url('/hijos/'.$hijo->id) }}" method="post">
        @csrf
        @method('PATCH')
        @include('hijos.form', ['modo' => 'Actualizar'])
    </form>
</div>
@endsection
