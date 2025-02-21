@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4 text-center">Registrar Días Históricos</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('historial-vacacion.store') }}" method="POST">
        @csrf
        <div class="form-group mb-3">
            <label for="trabajador_id">Empleado</label>
            <select name="trabajador_id" class="form-control" required>
                @foreach ($trabajadores as $trabajador)
                    <option value="{{ $trabajador->id }}">{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" class="form-control" name="fecha_inicio" required>
        </div>

        <div class="form-group mb-3">
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" class="form-control" name="fecha_fin" required>
        </div>

        <div class="form-group mb-3">
            <label for="dias_laborales">Días Laborales</label>
            <input type="number" class="form-control" name="dias_laborales" required>
        </div>

        <div class="form-group mb-3">
            <label for="tipo_dia">Tipo de Día</label>
            <select name="tipo_dia" class="form-control" required>
                <option value="vacaciones">Vacaciones</option>
                <option value="administrativo">Administrativo</option>
                <option value="sin_goce_de_sueldo">Permiso sin goce de sueldo</option>
                <option value="permiso_fuerza_mayor">Permiso Fuerza Mayor</option>
                <option value="licencia_medica">Licencia Médica</option> <!-- Nueva opción -->
            </select>
        </div>
        

        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
</div>
@endsection
