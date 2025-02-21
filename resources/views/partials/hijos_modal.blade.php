<div class="modal fade" id="hijosModal" tabindex="-1" role="dialog" aria-labelledby="hijosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hijosModalLabel">Gestionar Hijos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="hijos-container">
                    @if(isset($hijos) && $hijos->count() > 0)
                        @foreach($hijos as $index => $hijo)
                            <div class="hijo-form">
                                <!-- Otros campos del hijo -->
                                <div class="form-group">
                                    <label for="hijos_{{ $index }}_nombre">Nombre</label>
                                    <input type="text" name="hijos[{{ $index }}][nombre]" class="form-control" value="{{ $hijo->nombre }}">
                                </div>
                                <div class="form-group">
                                    <label for="hijos_{{ $index }}_genero">Género</label>
                                    <select name="hijos[{{ $index }}][genero]" class="form-control">
                                        <option value="Masculino" {{ $hijo->genero == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                        <option value="Femenino" {{ $hijo->genero == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="hijos_{{ $index }}_parentesco">Parentesco</label>
                                    <input type="text" name="hijos[{{ $index }}][parentesco]" class="form-control" value="{{ $hijo->parentesco }}">
                                </div>
                                <div class="form-group">
                                    <label for="hijos_{{ $index }}_fecha_nacimiento">Fecha de Nacimiento</label>
                                    <input type="date" name="hijos[{{ $index }}][fecha_nacimiento]" class="form-control" value="{{ $hijo->fecha_nacimiento }}">
                                </div>
                                <!-- Eliminar el campo de edad porque se calculará automáticamente -->
                            </div>
                        @endforeach
                    @else
                        <div class="hijo-form">
                            <!-- Campos para un nuevo hijo -->
                            <div class="form-group">
                                <label for="hijos_0_nombre">Nombre</label>
                                <input type="text" name="hijos[0][nombre]" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="hijos_0_genero">Género</label>
                                <select name="hijos[0][genero]" class="form-control">
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="hijos_0_parentesco">Parentesco</label>
                                <input type="text" name="hijos[0][parentesco]" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="hijos_0_fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" name="hijos[0][fecha_nacimiento]" class="form-control">
                            </div>
                            <!-- Eliminar el campo de edad porque se calculará automáticamente -->
                        </div>
                    @endif
                </div>
                <button type="button" id="add-hijo" class="btn btn-success mt-3">Añadir Hijo</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Guardar</button>
            </div>
        </div>
    </div>
</div>












<script>
    document.getElementById('add-hijo').addEventListener('click', function() {
        var container = document.getElementById('hijos-container');
        var index = container.children.length;
        var template = `
            <div class="hijo-form">
                <div class="form-group">
                    <label for="hijos_${index}_nombre">Nombre</label>
                    <input type="text" name="hijos[${index}][nombre]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="hijos_${index}_genero">Género</label>
                    <select name="hijos[${index}][genero]" class="form-control">
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hijos_${index}_parentesco">Parentesco</label>
                    <input type="text" name="hijos[${index}][parentesco]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="hijos_${index}_fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" name="hijos[${index}][fecha_nacimiento]" class="form-control">
                </div>
                <!-- Hemos eliminado el campo de edad porque se calcula automáticamente -->
            </div>
        `;
        container.insertAdjacentHTML('beforeend', template);
    });
</script>

