@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center">Crear Nueva Factura</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('facturas.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="proveedor_id">Proveedor</label>
            <select name="proveedor_id" id="proveedor_id" class="form-control" required>
                <option value="">Seleccione un proveedor</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" {{ old('proveedor_id', $proveedorSeleccionado) == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->razon_social }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="centro_costo">Centro de Costo</label>
            <input type="text" name="centro_costo" id="centro_costo" class="form-control" value="{{ old('centro_costo') }}" required>
        </div>

        <div class="form-group">
            <label for="empresa_id">Empresa</label>
            <select name="empresa_id" id="empresa_id" class="form-control" required>
                <option value="">Seleccione una empresa</option>
                @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}" {{ old('empresa_id') == $empresa->id ? 'selected' : '' }}>
                        {{ $empresa->Nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        

        <div class="form-group">
            <label for="glosa">Glosa</label>
            <textarea name="glosa" id="glosa" class="form-control">{{ old('glosa') }}</textarea>
        </div>

        <div class="form-group">
            <label for="comentario">Comentario</label>
            <textarea name="comentario" id="comentario" class="form-control">{{ old('comentario') }}</textarea>
        </div>

        <div class="form-group">
            <label for="pagador">Pagador</label>
            <input type="text" name="pagador" id="pagador" class="form-control" value="{{ old('pagador') }}" required>
        </div>

        <div class="form-group">
            <label for="tipo_documento">Tipo de Documento</label>
            <select name="tipo_documento" id="tipo_documento" class="form-control" required>
                @foreach(['Factura', 'Boleta', 'Boleta Honorarios', 'Boleta de Tercero', 'Documento', 'Factura Exenta', 'Factura Pendiente'] as $tipo_documento)
                    <option value="{{ $tipo_documento }}" {{ old('tipo_documento', $factura->tipo_documento ?? '') == $tipo_documento ? 'selected' : '' }}>
                        {{ $tipo_documento }}
                    </option>
                @endforeach
            </select>
        </div>


        <div class="form-group">
            <label for="status">Estado</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pendiente" {{ old('status') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="Pagado" {{ old('status') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                <option value="Abonado" {{ old('status') == 'Abonado' ? 'selected' : '' }}>Abonado</option>
                <option value="No Pagar" {{ old('status') == 'No Pagar' ? 'selected' : '' }}>No Pagar</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success mt-3">Guardar Factura</button>
        <a href="{{ route('facturas.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</div>
@endsection
