<!-- resources/views/estado_civil/index.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Estados Civiles</h1>
    <a href="<?php echo e(route('estado_civil.create')); ?>" class="btn btn-primary">Agregar Estado Civil</a>
    <table class="table mt-3">
        <thead>
            <tr>
                
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $estadoCivils; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $estadoCivil): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    
                    <td><?php echo e($estadoCivil->Nombre); ?></td>
                    <td>
                        <a href="<?php echo e(route('estado_civil.edit', $estadoCivil->id)); ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i>Editar
                        </a>
                        <form action="<?php echo e(route('estado_civil.destroy', $estadoCivil->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Confirmas que quieres eliminar este estado civil?')">
                                <i class="fas fa-trash-alt"></i>Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/estado_civil/index.blade.php ENDPATH**/ ?>