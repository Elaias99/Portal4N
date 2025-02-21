@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Agregar Nuevo Proveedor</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('proveedores.store') }}" method="POST">
        @csrf
        @include('proveedores.form')
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
@endsection
