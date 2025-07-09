<div class="form-group">
    <label for="tipo">Tipo de documento</label>
    <select name="tipo" id="tipo" class="form-control" required>
        <option value="">-- Seleccione --</option>
        <option value="Contrato" {{ old('tipo', $contrato->tipo ?? '') == 'Contrato' ? 'selected' : '' }}>Contrato</option>
        <option value="Anexo" {{ old('tipo', $contrato->tipo ?? '') == 'Anexo' ? 'selected' : '' }}>Anexo</option>
    </select>
</div>

<div class="form-group">
    <label for="fecha_inicio_contrato">Fecha de firma</label>
    <input type="date" name="fecha_inicio_contrato" id="fecha_inicio_contrato" class="form-control"
           value="{{ old('fecha_inicio_contrato', isset($contrato->fecha_inicio_contrato) ? \Carbon\Carbon::parse($contrato->fecha_inicio_contrato)->format('Y-m-d') : '') }}"
           required>
</div>


<div class="form-group">
    <label for="estado">Estado</label>
    <select name="estado" id="estado" class="form-control" required>
        <option value="">-- Seleccione --</option>
        <option value="Firmado" {{ old('estado', $contrato->estado ?? '') == 'Firmado' ? 'selected' : '' }}>Firmado</option>
        <option value="Pendiente" {{ old('estado', $contrato->estado ?? '') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
        <option value="Rechazado" {{ old('estado', $contrato->estado ?? '') == 'Rechazado' ? 'selected' : '' }}>Rechazado</option>
    </select>
</div>



<div class="form-group">
    <label for="firmado_por">Firmado por</label>
    <input type="text" name="firmado_por" id="firmado_por" class="form-control"
           value="{{ old('firmado_por', $contrato->firmado_por ?? '') }}">
</div>

<div class="form-group">
    <label for="archivo">Archivo PDF (opcional)</label>
    <input type="file" name="archivo" id="archivo" class="form-control-file" accept="application/pdf">

    @if (!empty($contrato?->archivo))
        <small class="form-text text-muted mt-1">
            Archivo actual:
            <a href="{{ route('contratos.download', $contrato->id) }}" target="_blank">Ver PDF actual</a>
        </small>
    @endif
</div>
