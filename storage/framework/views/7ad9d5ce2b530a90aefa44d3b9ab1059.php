

<?php $__env->startSection('content'); ?>


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
                <div class="card-header text-center" style="padding: 20px 0;">
                    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">
                        Editar a <?php echo e($empleado->Nombre); ?> <?php echo e($empleado->ApellidoPaterno); ?> <?php echo e($empleado->ApellidoMaterno); ?>

                    </h1>
                </div>
            
        </div>
    </div>
    
    <form action="<?php echo e(url('/empleados/'.$empleado->id)); ?>" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo e(method_field('PATCH')); ?>

        <?php echo $__env->make('empleados.form', ['modo' => 'Actualizar'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </form>
</div>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/empleados/edit.blade.php ENDPATH**/ ?>