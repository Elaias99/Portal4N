@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center">Editar Talla</h1>
    <form action="{{ route('tallas.update', $talla->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('tallas.form', ['modo' => 'Editar'])
    </form>
</div>
@endsection

