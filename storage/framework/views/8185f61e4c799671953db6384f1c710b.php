

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Crear Región</h1>
    <form action="<?php echo e(route('regions.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('regions.form', ['modo' => 'Crear'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </form>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/regions/create.blade.php ENDPATH**/ ?>