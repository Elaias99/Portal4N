@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar AFP</h1>
    <form action="{{ route('afps.update', $afp->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('afps.form')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
