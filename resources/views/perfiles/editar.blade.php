
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Editar Perfil</h1>

    <!-- Mostrar mensajes de éxito o errores -->
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario de edición -->
    <form action="{{ route('empleados.perfil.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="Nombre">Nombre</label>
            <input type="text" name="Nombre" class="form-control" value="{{ old('Nombre', $trabajador->Nombre) }}" required>
        </div>

        <div class="form-group">
            <label for="ApellidoPaterno">Apellido Paterno</label>
            <input type="text" name="ApellidoPaterno" class="form-control" value="{{ old('ApellidoPaterno', $trabajador->ApellidoPaterno) }}" required>
        </div>

        

        <div class="form-group">
            <label for="numero_celular">Número Celular</label>
            <input type="text" name="numero_celular" class="form-control" value="{{ old('numero_celular', $trabajador->numero_celular) }}">
        </div>

        <div class="form-group">
            <label for="nombre_emergencia">Nombre Contacto de Emergencia</label>
            <input type="text" name="nombre_emergencia" class="form-control" value="{{ old('nombre_emergencia', $trabajador->nombre_emergencia) }}">
        </div>

        <div class="form-group">
            <label for="contacto_emergencia">Teléfono Contacto de Emergencia</label>
            <input type="text" name="contacto_emergencia" class="form-control" value="{{ old('contacto_emergencia', $trabajador->contacto_emergencia) }}">
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="{{ route('empleados.perfil') }}" class="btn btn-outline-primary btn-block">Atrás</a>


    </form>
</div>
@endsection
