<!-- resources/views/estado_civil/form.blade.php -->
<div class="form-group">
    <label for="Nombre">Nombre</label>
    <input type="text" class="form-control <?php $__errorArgs = ['Nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="Nombre" name="Nombre" value="<?php echo e(old('Nombre', $estadoCivil->Nombre ?? '')); ?>" required>
    <?php $__errorArgs = ['Nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="invalid-feedback">
            <?php echo e($message); ?>

        </div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>



<!-- Botones de acción -->
<div class="mt-4">
    
    <a href="<?php echo e(route('estado_civil.index')); ?>" class="btn btn-secondary">Volver al índice</a>
</div>
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/estado_civil/form.blade.php ENDPATH**/ ?>