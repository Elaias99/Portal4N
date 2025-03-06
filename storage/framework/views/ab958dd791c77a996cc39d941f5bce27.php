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
                    <?php if(isset($tipoVestimentas) && $tipoVestimentas->count() > 0): ?>
                        <?php $__currentLoopData = $tipoVestimentas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tipoVestimenta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="form-group">
                                <label for="talla">Talla de <?php echo e($tipoVestimenta->Nombre); ?></label>

                                <?php
                                    // Determina si la talla guardada es personalizada
                                    $esPersonalizado = isset($tallas[$tipoVestimenta->id]) && !in_array($tallas[$tipoVestimenta->id]->talla, ['S', 'M', 'L', 'XL', 'XXL']);
                                ?>

                                <?php if(in_array($tipoVestimenta->Nombre, ['Polera', 'Polerón', 'Pantalón', 'Geólogo', 'Jacketa'])): ?>
                                    <!-- Dropdown con opción Otro -->
                                    <select name="tallas[<?php echo e($tipoVestimenta->id); ?>][talla]" class="form-control" onchange="toggleCustomTalla(this, '<?php echo e($tipoVestimenta->id); ?>')">
                                        <option value="">Seleccione una talla</option>
                                        <option value="S" <?php echo e(old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'S' ? 'selected' : ''); ?>>S</option>
                                        <option value="M" <?php echo e(old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'M' ? 'selected' : ''); ?>>M</option>
                                        <option value="L" <?php echo e(old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'L' ? 'selected' : ''); ?>>L</option>
                                        <option value="XL" <?php echo e(old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'XL' ? 'selected' : ''); ?>>XL</option>
                                        <option value="XXL" <?php echo e(old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '') == 'XXL' ? 'selected' : ''); ?>>XXL</option>
                                        <option value="otro" <?php echo e($esPersonalizado ? 'selected' : ''); ?>>Otro</option>
                                    </select>

                                    <!-- Campo de texto solo si seleccionan "Otro" -->
                                    <input type="text" name="tallas[<?php echo e($tipoVestimenta->id); ?>][custom]" 
                                           class="form-control mt-2" 
                                           placeholder="Escriba la talla" 
                                           id="custom-talla-<?php echo e($tipoVestimenta->id); ?>" 
                                           style="<?php echo e($esPersonalizado ? 'display:block;' : 'display:none;'); ?>" 
                                           value="<?php echo e($esPersonalizado ? $tallas[$tipoVestimenta->id]->talla : ''); ?>">
                                <?php else: ?>
                                    <!-- Campo de texto directo para casos como zapatos -->
                                    <input type="text" name="tallas[<?php echo e($tipoVestimenta->id); ?>][talla]" 
                                           class="form-control" 
                                           value="<?php echo e(old('tallas.'.$tipoVestimenta->id.'.talla', $tallas[$tipoVestimenta->id]->talla ?? '')); ?>">
                                <?php endif; ?>

                                <?php $__errorArgs = ['tallas.'.$tipoVestimenta->id.'.talla'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="text-danger"><?php echo e($message); ?></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <p>No hay tipos de vestimenta disponibles.</p>
                    <?php endif; ?>
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
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/partials/tallas_modal.blade.php ENDPATH**/ ?>