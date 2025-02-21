@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear AFP</h1>

    <form action="{{ route('afps.store') }}" method="POST">
        @csrf
        @include('afps.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

@endsection
