

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Localidades de los Empleados</h1>
    
    <div class="row">
        <?php $__currentLoopData = $empleados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empleado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm" style="border-left: 4px solid #007bff;">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo e($empleado->Nombre); ?> <?php echo e($empleado->ApellidoPaterno); ?> <?php echo e($empleado->ApellidoMaterno); ?></h5>
                    <p class="card-text"><strong>Comuna:</strong> <?php echo e($empleado->comuna->Nombre); ?></p>
                    <p class="card-text"><strong>Región:</strong> <?php echo e($empleado->comuna->region->Nombre); ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>


</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/empleados/localidades.blade.php ENDPATH**/ ?>