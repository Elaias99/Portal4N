@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Clasificar Comuna: <strong>{{ $comuna->Nombre }}</strong></h2>

    <form method="POST" action="{{ route('clasificacion-operativa.update', $comuna->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="proveedor_id" class="form-label">Proveedor / Operador</label>
            <select name="proveedor_id" id="proveedor_id" class="form-control" required>
                <option value="">Seleccione...</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}"
                        {{ optional($comuna->clasificacionOperativa)->proveedor_id == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->razon_social }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="zona_id" class="form-label">Zona</label>
            <select name="zona_id" id="zona_id" class="form-control" required>
                <option value="">Seleccione...</option>
                @foreach($zonas as $zona)
                    <option value="{{ $zona->id }}" {{ optional($comuna->clasificacionOperativa)->zona_id == $zona->id ? 'selected' : '' }}>
                        {{ $zona->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- <div class="mb-3">
            <label for="tipo_zona_id" class="form-label">Tipo de Zona</label>
            <select name="tipo_zona_id" id="tipo_zona_id" class="form-control" required>
                <option value="">Seleccione...</option>
                @foreach($tiposZona as $tipo)
                    <option value="{{ $tipo->id }}" {{ optional($comuna->clasificacionOperativa)->tipo_zona_id == $tipo->id ? 'selected' : '' }}>
                        {{ $tipo->nombre }}
                    </option>
                @endforeach
            </select>
        </div> --}}

        <div class="mb-3">
            <label for="subzona_id" class="form-label">Subzona</label>
            <select name="subzona_id" id="subzona_id" class="form-control" required>
                <option value="">Seleccione...</option>
                @foreach($subzonas as $subzona)
                    <option value="{{ $subzona->id }}" {{ optional($comuna->clasificacionOperativa)->subzona_id == $subzona->id ? 'selected' : '' }}>
                        {{ $subzona->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="comuna_matriz" class="form-label">Comuna Matriz</label>
            <select name="comuna_matriz" id="comuna_matriz" class="form-control">
                <option value="">Seleccione...</option>
                @foreach($comunasTodas as $item)
                    <option value="{{ $item->Nombre }}" 
                        {{ old('comuna_matriz', optional($comuna->clasificacionOperativa)->comuna_matriz) == $item->Nombre ? 'selected' : '' }}>
                        {{ $item->Nombre }}
                    </option>
                @endforeach
            </select>            
        </div>

        <div class="mb-3">
            <label for="zona_ruta_geografica_id" class="form-label">Ruta Geográfica</label>
            <select name="zona_ruta_geografica_id" id="zona_ruta_geografica_id" class="form-control" required>
                <option value="">Seleccione...</option>
                @foreach($rutasGeograficas as $ruta)
                    <option value="{{ $ruta->id }}"
                        {{ optional($comuna->clasificacionOperativa)->zona_ruta_geografica_id == $ruta->id ? 'selected' : '' }}>
                        {{ $ruta->nombre }}
                    </option>
                @endforeach
            </select>
        </div>






        <div class="mb-3">
            <label for="cobertura_id" class="form-label">Cobertura</label>
            <select name="cobertura_id" id="cobertura_id" class="form-control">
                <option value="">Seleccione...</option>
                @foreach($coberturas as $cobertura)
                    <option value="{{ $cobertura->id }}"
                        {{ optional($comuna->clasificacionOperativa)->cobertura_id == $cobertura->id ? 'selected' : '' }}>
                        {{ $cobertura->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="provincia_id" class="form-label">Provincia</label>
            <select name="provincia_id" id="provincia_id" class="form-control">
                <option value="">Seleccione...</option>
                @foreach($provincias as $provincia)
                    <option value="{{ $provincia->id }}"
                        {{ optional($provincia->clasificacionOperativa)->provincia_id == $provincia->id ? 'selected' : '' }}>
                        {{ $provincia->nombre }}
                    </option>
                @endforeach
            </select>
        </div>


        <div class="mb-3">
            <label class="form-label">Frecuencia de Distribución (Días)</label>
            <div class="form-check">
                @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $dia)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="frecuencia_dias[]" value="{{ $dia }}"
                            {{ in_array($dia, old('frecuencia_dias', $frecuenciaDias ?? [])) ? 'checked' : '' }}>
                        <label class="form-check-label">{{ $dia }}</label>
                    </div>
                @endforeach
            </div>
        </div>






        <button type="submit" class="btn btn-success">Guardar Clasificación</button>
        <a href="{{ route('clasificacion-operativa.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
