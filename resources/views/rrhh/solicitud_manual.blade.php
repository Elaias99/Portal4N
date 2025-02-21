@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Generar Solicitud Manual de Días</h2>

    <div class="mb-3">
        <label for="vacacion_id" class="form-label">ID de la Vacación</label>
        <input type="text" name="vacacion_id" id="vacacion_id" class="form-control" 
               value="{{ \App\Models\Vacacion::max('id') + 1 }}" readonly>
    </div>
    
    

    <form action="{{ route('rrhh.generar-pdf') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="trabajador_id" class="form-label">Seleccionar Trabajador</label>
            <select name="trabajador_id" id="trabajador_id" class="form-control" required>
                @foreach($trabajadores as $trabajador)
                    <option value="{{ $trabajador->id }}">{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="tipo_dia" class="form-label">Tipo de Día</label>
            <select name="tipo_dia" id="tipo_dia" class="form-control" required>
                <option value="vacaciones">Vacaciones</option>
                <option value="administrativo">Día Administrativo</option>
                <option value="sin_goce_de_sueldo">Sin Goce de Sueldo</option>
                <option value="permiso_fuerza_mayor">Permiso Fuerza Mayor</option>
                <option value="licencia_medica">Licencia Médica</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="fecha_fin" class="form-label">Fecha de Fin</label>
            <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="comentario" class="form-label">Comentario (Opcional)</label>
            <textarea name="comentario" id="comentario" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Generar Solicitud</button>
    </form>
</div>
@endsection

