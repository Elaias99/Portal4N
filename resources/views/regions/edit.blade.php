@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Región</h1>
    <form action="{{ route('regions.update', $region->id) }}" method="POST">
        @csrf
        @method('PATCH')
        @include('regions.form', ['modo' => 'Actualizar'])
    </form>
</div>
@endsection

