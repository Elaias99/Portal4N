<!-- resources/views/estado_civil/create.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Agregar Estado Civil</h1>
    <form action="<?php echo e(route('estado_civil.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('estado_civil.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/estado_civil/create.blade.php ENDPATH**/ ?>