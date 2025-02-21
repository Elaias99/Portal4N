@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Región</h1>
    <form action="{{ route('regions.store') }}" method="POST">
        @csrf
        @include('regions.form', ['modo' => 'Crear'])
    </form>
</div>
@endsection

