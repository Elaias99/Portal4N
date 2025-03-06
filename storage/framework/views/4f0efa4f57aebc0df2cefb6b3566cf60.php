

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Crear Nueva Compra</h1>
    <?php echo $__env->make('compras.form', [
        'action' => route('compras.store'),
        'method' => 'POST',
        'compra' => new \App\Models\Compra(),
        'proveedores' => $proveedores,
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <a href="<?php echo e(route('compras.index')); ?>" class="btn btn-primary">
        ← Regresar
    </a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/compras/create.blade.php ENDPATH**/ ?>