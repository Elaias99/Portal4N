







<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Lista de Comunas por Región</h1>
    <a href="<?php echo e(route('comunas.create')); ?>" class="btn btn-primary mb-3" style="background-color: #007bff; border-color: #007bff; font-size: 16px; padding: 10px 20px; border-radius: 5px;">
        <i class="fas fa-plus"></i> Crear Comuna
    </a>
    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="accordion" id="accordionRegions">
        <?php $__currentLoopData = $regions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?php echo e($region->id); ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo e($region->id); ?>" aria-expanded="false" aria-controls="collapse<?php echo e($region->id); ?>">
                        <?php echo e($region->Nombre); ?> (<?php echo e($region->Numero); ?>)
                    </button>
                </h2>
                <div id="collapse<?php echo e($region->id); ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo e($region->id); ?>" data-bs-parent="#accordionRegions">
                    <div class="accordion-body">
                        <table class="table table-light table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nombre Comuna</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $region->comunas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comuna): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($comuna->Nombre); ?></td>
                                        <td>
                                            <a class="btn btn-warning" href="<?php echo e(route('comunas.edit', $comuna->id)); ?>">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form action="<?php echo e(route('comunas.destroy', $comuna->id)); ?>" method="POST" style="display:inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-danger" type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta Comuna?')">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/comunas/index.blade.php ENDPATH**/ ?>