

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Detalle de la Factura</h1>
    <div class="card">
        <div class="card-header">
            Factura #<?php echo e($factura->id); ?>

        </div>
        <div class="card-body">
            <p><strong>Proveedor:</strong> <?php echo e($factura->proveedor->razon_social); ?></p>
            <p><strong>Empresa:</strong> <?php echo e($factura->empresa->nombre); ?></p>
            <p><strong>Glosa:</strong> <?php echo e($factura->glosa); ?></p>
            <p><strong>Estado:</strong> <?php echo e($factura->status); ?></p>
            <p><strong>Fecha de Creación:</strong> <?php echo e($factura->created_at); ?></p>
        </div>
    </div>
    <a href="<?php echo e(route('facturas.index')); ?>" class="btn btn-primary mt-3">Volver al Listado</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/facturas/detail.blade.php ENDPATH**/ ?>