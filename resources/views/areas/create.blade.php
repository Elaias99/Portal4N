@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Crear Nueva Área</h2>

    <form action="{{ route('areas.store') }}" method="POST">
        @csrf
        @include('areas.form', ['area' => new \App\Models\Area])
    </form>
</div>
@endsection
