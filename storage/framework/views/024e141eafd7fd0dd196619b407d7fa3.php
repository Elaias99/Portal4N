

<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h3 class="fw-bold text-dark">📌 Registro de Asistencia</h3>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <form action="<?php echo e(route('asistencia.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Asistió</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $empleados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empleado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($empleado->Nombre); ?> <?php echo e($empleado->ApellidoPaterno); ?></td>
                    <td>
                        <input type="checkbox" name="asistencias[<?php echo e($empleado->id); ?>]" value="1"
                               <?php echo e($empleado->asistencias->where('fecha', now()->toDateString())->first()?->asistio ? 'checked' : ''); ?>>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Guardar Asistencia</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/asistencia/index.blade.php ENDPATH**/ ?>