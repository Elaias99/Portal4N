@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center">Crear Talla</h1>
    <form action="{{ route('tallas.store') }}" method="POST">
        @csrf
        @include('tallas.form', ['modo' => 'Crear'])
    </form>
</div>
@endsection

