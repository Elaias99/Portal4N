@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Solicitar Modificación</h1>

    <form method="POST" action="{{ route('solicitudes.store') }}" enctype="multipart/form-data">
        @csrf

        <!-- Campo a Modificar -->
        <div class="form-group">
            <label for="campo">¿Qué campo deseas modificar?</label>
            <select name="campo" id="campo" class="form-control">
                <option value="afp">AFP</option>
                <option value="cargo">Cargo</option>
                <option value="salario_bruto">Salario Bruto</option>
                <option value="fecha_inicio_trabajo">Fecha de Ingreso</option>
                <option value="fecha_inicio_contrato">Fecha de Inicio de Contrato</option>
                <option value="banco">Banco</option>
                <option value="numero_cuenta">Número de Cuenta</option>
                <option value="tipo_cuenta">Tipo de Cuenta</option>
                <option value="estado_civil">Estado Civil</option>
                <option value="sistema_trabajo">Sistema de Trabajo</option>
                <option value="turno">Turno</option>
                <option value="situacion">Situación Laboral</option>
                <option value="comuna">Comuna</option>
                <option value="contrato_firmado">Contrato Firmado</option>
                <option value="anexo_contrato">Anexo de Contrato</option>
            </select>
        </div>

        <!-- Descripción del Cambio -->
        <div class="form-group">
            <label for="descripcion">Describe el cambio</label>
            <textarea name="descripcion" id="descripcion" rows="4" class="form-control" placeholder="Explica el motivo de tu solicitud"></textarea>
        </div>


        <!-- Campo para adjuntar archivos -->
        <div class="form-group">
            <label for="archivo">Adjuntar archivo (opcional):</label>
            <input type="file" name="archivo" class="form-control" id="archivo">
        </div>

        <!-- Botón para enviar la solicitud -->
        <button type="submit" class="btn btn-success">Enviar Solicitud</button>
    </form>
</div>
@endsection
