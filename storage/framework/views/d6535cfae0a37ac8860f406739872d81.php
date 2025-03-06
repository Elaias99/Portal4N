


<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="mb-4">Editar Perfil</h1>

    <!-- Mostrar mensajes de éxito o errores -->
    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Formulario de edición -->
    <form action="<?php echo e(route('empleados.perfil.update')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="form-group">
            <label for="Nombre">Nombre</label>
            <input type="text" name="Nombre" class="form-control" value="<?php echo e(old('Nombre', $trabajador->Nombre)); ?>" required>
        </div>

        <div class="form-group">
            <label for="ApellidoPaterno">Apellido Paterno</label>
            <input type="text" name="ApellidoPaterno" class="form-control" value="<?php echo e(old('ApellidoPaterno', $trabajador->ApellidoPaterno)); ?>" required>
        </div>

        

        <div class="form-group">
            <label for="numero_celular">Número Celular</label>
            <input type="text" name="numero_celular" class="form-control" value="<?php echo e(old('numero_celular', $trabajador->numero_celular)); ?>">
        </div>

        <div class="form-group">
            <label for="nombre_emergencia">Nombre Contacto de Emergencia</label>
            <input type="text" name="nombre_emergencia" class="form-control" value="<?php echo e(old('nombre_emergencia', $trabajador->nombre_emergencia)); ?>">
        </div>

        <div class="form-group">
            <label for="contacto_emergencia">Teléfono Contacto de Emergencia</label>
            <input type="text" name="contacto_emergencia" class="form-control" value="<?php echo e(old('contacto_emergencia', $trabajador->contacto_emergencia)); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="<?php echo e(route('empleados.perfil')); ?>" class="btn btn-outline-primary btn-block">Atrás</a>


    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/perfiles/editar.blade.php ENDPATH**/ ?>