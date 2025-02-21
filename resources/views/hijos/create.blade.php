@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Hijo</h1>
    <form action="{{ url('/hijos') }}" method="post">
        @csrf
        @include('hijos.form', ['modo' => 'Crear'])
    </form>
</div>
@endsection
