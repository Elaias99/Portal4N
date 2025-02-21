<div class="modal fade" id="tallasModal" tabindex="-1" role="dialog" aria-labelledby="tallasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tallasModalLabel">Gestionar Tallas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="tipo_vestimenta">Tipo de Vestimenta</label>
                    @if(isset($tipoVestimentas) && $tipoVestimentas->count() > 0)
                        @foreach($tipoVestimentas as $tipoVestimenta)
                            <div class="form-group">
                                <label for="talla">Talla de {{ $tipoVestimenta->Nombre }}</label>

                                @php
                                    // Determina si la talla guardada es personalizada
                                    $esPersonalizado = isset($tallas[$tipoVestimenta->id]) && !in_array($tallas[$tipoVestimenta->id]->talla, ['S', 'M', 'L', 'XL', 'XXL']);
                                @endphp

                                @if(in_array($tipoVestimenta->Nombre, ['Polera', 'Polerón', 'Pantalón', 'Geólogo', 'Jacketa']))
                                    <!-- Dropdown con opción Otro -->
                                    <select name="tallas[{{ $tipoVestimenta->id }}][talla]" class="form-control" onchange="toggleCustomTalla(this, '{{ $tipoVestimenta->id }}')">
                                        <option value="">Seleccione una talla</option>
                                        <option value="S" {{ old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'S' ? 'selected' : '' }}>S</option>
                                        <option value="M" {{ old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'M' ? 'selected' : '' }}>M</option>
                                        <option value="L" {{ old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'L' ? 'selected' : '' }}>L</option>
                                        <option value="XL" {{ old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'XL' ? 'selected' : '' }}>XL</option>
                                        <option value="XXL" {{ old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'XXL' ? 'selected' : '' }}>XXL</option>
                                        <option value="otro" {{ $esPersonalizado ? 'selected' : '' }}>Otro</option>
                                    </select>

                                    <!-- Campo de texto solo si seleccionan "Otro" -->
                                    <input type="text" name="tallas[{{ $tipoVestimenta->id }}][custom]" 
                                           class="form-control mt-2" 
                                           placeholder="Escriba la talla" 
                                           id="custom-talla-{{ $tipoVestimenta->id }}" 
                                           style="{{ $esPersonalizado ? 'display:block;' : 'display:none;' }}" 
                                           value="{{ $esPersonalizado ? $tallas[$tipoVestimenta->id]->talla : '' }}">
                                @else
                                    <!-- Campo de texto directo para casos como zapatos -->
                                    <input type="text" name="tallas[{{ $tipoVestimenta->id }}][talla]" 
                                           class="form-control" 
                                           value="{{ old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') }}">
                                @endif

                                @error('tallas.'.$tipoVestimenta->id.'.talla')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    @else
                        <p>No hay tipos de vestimenta disponibles.</p>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleCustomTalla(selectElement, tipoVestimentaId) {
        const customInput = document.getElementById(`custom-talla-${tipoVestimentaId}`);
        if (selectElement.value === 'otro') {
            customInput.style.display = 'block';
        } else {
            customInput.style.display = 'none';
            customInput.value = ''; // Limpia el valor del campo si se selecciona otra opción
        }
    }
</script>
