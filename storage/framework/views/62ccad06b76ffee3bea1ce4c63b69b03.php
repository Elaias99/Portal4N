

<?php $__env->startSection('content'); ?>

<div class="container">

    <h1>Crear Cargo</h1>

    <form action="<?php echo e(route('cargos.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('cargos.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/cargos/create.blade.php ENDPATH**/ ?>