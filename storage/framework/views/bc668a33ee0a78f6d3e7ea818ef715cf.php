





<div class="form-group">
    <label for="Nombre">Nombre:</label>
    <input type="text" name="Nombre" class="form-control" value="<?php echo e(old('Nombre', $cargo->Nombre ?? '')); ?>" required>
    
    <?php if($errors->has('Nombre')): ?>
        <span class="text-danger"><?php echo e($errors->first('Nombre')); ?></span>
    <?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/cargos/form.blade.php ENDPATH**/ ?>