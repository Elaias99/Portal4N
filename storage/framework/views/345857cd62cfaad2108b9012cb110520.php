

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Lista de Sistemas de Trabajo</h1>
    <a href="<?php echo e(route('sistema_trabajos.create')); ?>" class="btn btn-primary mb-3">Crear Nuevo Sistema de Trabajo</a>
    
    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>

        </div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $sistemasTrabajo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sistema): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                
                <td><?php echo e($sistema->nombre); ?></td>
                <td>
                    <a href="<?php echo e(route('sistema_trabajos.edit', $sistema->id)); ?>" class="btn btn-warning">Editar</a>
                    <form action="<?php echo e(route('sistema_trabajos.destroy', $sistema->id)); ?>" method="POST" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este sistema?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/sistema_trabajos/index.blade.php ENDPATH**/ ?>