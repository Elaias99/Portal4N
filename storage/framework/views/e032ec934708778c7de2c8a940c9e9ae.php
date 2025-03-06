



<!-- resources/views/empresas/index.blade.php -->



<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Gestionar Empresas</h1>

    <!-- Botón para redirigir a la página de creación -->
    <a href="<?php echo e(route('empresas.create')); ?>" class="btn btn-primary mb-3">Añadir Empresa</a>

    <!-- Lista de empresas -->
    <table class="table mt-3">
        <thead>
            <tr>
                <th>Logo</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $empresas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empresa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td>
                        <?php if($empresa->logo): ?>
                            <img src="<?php echo e(asset('storage/' . $empresa->logo)); ?>" alt="Logo de <?php echo e($empresa->Nombre); ?>" style="max-height: 50px;">
                        <?php else: ?>
                            No hay logo
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($empresa->Nombre); ?></td>
                    <td>
                        <!-- Enlace para editar empresa -->
                        <a href="<?php echo e(route('empresas.edit', $empresa->id)); ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <form action="<?php echo e(route('empresas.destroy', $empresa->id)); ?>" method="POST" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar la empresa?')">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/empresas/index.blade.php ENDPATH**/ ?>