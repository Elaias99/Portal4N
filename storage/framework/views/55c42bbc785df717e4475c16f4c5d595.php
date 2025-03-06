

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Editar Compra</h1>

    


    <?php echo $__env->make('compras.form', [
        'action' => route('compras.update', $compra->id),
        'method' => 'PUT',
        'compra' => $compra,
        'proveedores' => $proveedores,
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Botón para regresar atrás -->
    <a href="<?php echo e(route('compras.index')); ?>" class="btn btn-primary">
        ← Regresar
    </a>
    
</div>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/compras/edit.blade.php ENDPATH**/ ?>