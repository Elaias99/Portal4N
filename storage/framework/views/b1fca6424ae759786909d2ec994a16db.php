

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Lista de Turnos</h1>
    <a href="<?php echo e(route('turnos.create')); ?>" class="btn btn-primary">Crear Nuevo Turno</a>
    
    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success mt-3">
            <?php echo e($message); ?>

        </div>
    <?php endif; ?>

    <table class="table mt-3">
        <thead>
            <tr>
                
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $turnos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $turno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                
                <td><?php echo e($turno->nombre); ?></td>
                <td>
                    <a href="<?php echo e(route('turnos.edit', $turno->id)); ?>" class="btn btn-warning">Editar</a>
                    <form action="<?php echo e(route('turnos.destroy', $turno->id)); ?>" method="POST" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este turno?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/turnos/index.blade.php ENDPATH**/ ?>