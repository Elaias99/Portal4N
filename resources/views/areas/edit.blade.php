@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Editar Área</h2>

    <form action="{{ route('areas.update', $area->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('areas.form')
    </form>
</div>
@endsection
