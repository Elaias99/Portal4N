

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Lista de Salud</h1>
    <a href="<?php echo e(route('saluds.create')); ?>" class="btn btn-primary">Crear salud</a>
    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>
    <table class="table table-bordered">
        <tr>
            
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
        <?php $__currentLoopData = $saluds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $salud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            
            <td><?php echo e($salud->Nombre); ?></td>
            <td>
                <a href="<?php echo e(route('saluds.edit', $salud->id)); ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i>Editar
                </a>
                <form action="<?php echo e(route('saluds.destroy', $salud->id)); ?>" method="POST" style="display:inline-block;">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este sistema de salud?')">
                        <i class="fas fa-trash-alt"></i>Eliminar
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/saluds/index.blade.php ENDPATH**/ ?>