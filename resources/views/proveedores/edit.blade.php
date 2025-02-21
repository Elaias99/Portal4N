@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Proveedor</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('proveedores.update', $proveedor->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('proveedores.form')
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>
@endsection
