

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Lista de Cargos</h1>
    <a href="<?php echo e(route('cargos.create')); ?>" class="btn btn-primary">Crear Cargo</a>
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
        <?php $__currentLoopData = $cargos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cargo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($cargo->Nombre); ?></td>
            <td>
                <a href="<?php echo e(route('cargos.edit', $cargo->id)); ?>" class="btn btn-warning">

                    <i class="fas fa-edit"></i>Editar
                </a>
                <form action="<?php echo e(route('cargos.destroy', $cargo->id)); ?>" method="POST" style="display:inline-block;">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este Cargo?');">
                        <i class="fas fa-trash-alt"></i>Eliminar
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/cargos/index.blade.php ENDPATH**/ ?>