

<?php $__env->startSection('content'); ?>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Registrar Días Históricos</h1>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <form action="<?php echo e(route('historial-vacacion.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group mb-3">
            <label for="trabajador_id">Empleado</label>
            <select name="trabajador_id" class="form-control" required>
                <?php $__currentLoopData = $trabajadores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trabajador): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($trabajador->id); ?>"><?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->ApellidoPaterno); ?> <?php echo e($trabajador->ApellidoMaterno); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" class="form-control" name="fecha_inicio" required>
        </div>

        <div class="form-group mb-3">
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" class="form-control" name="fecha_fin" required>
        </div>

        <div class="form-group mb-3">
            <label for="dias_laborales">Días Laborales</label>
            <input type="number" class="form-control" name="dias_laborales" required>
        </div>

        <div class="form-group mb-3">
            <label for="tipo_dia">Tipo de Día</label>
            <select name="tipo_dia" class="form-control" required>
                <option value="vacaciones">Vacaciones</option>
                <option value="administrativo">Administrativo</option>
                <option value="sin_goce_de_sueldo">Permiso sin goce de sueldo</option>
                <option value="permiso_fuerza_mayor">Permiso Fuerza Mayor</option>
                <option value="licencia_medica">Licencia Médica</option> <!-- Nueva opción -->
            </select>
        </div>
        

        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/historial_vacacion/create.blade.php ENDPATH**/ ?>