
                
                 









<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-primary">Listado de Regiones</h1>
        <a href="<?php echo e(route('regions.create')); ?>" class="btn btn-primary">Agregar Región</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <form action="<?php echo e(route('regions.index')); ?>" method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Buscar región..." value="<?php echo e(request()->query('search')); ?>">
                <button type="submit" class="btn btn-secondary">Buscar</button>
            </form>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        
                        <th>Nombre</th>
                        <th>Numero</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $regions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($region->Nombre); ?></td>
                            <td><?php echo e($region->Numero); ?></td>
                            
                            <td>
                                <a href="<?php echo e(route('regions.edit', $region->id)); ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <form action="<?php echo e(route('regions.destroy', $region->id)); ?>" method="POST" style="display:inline-block;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar esta región?');">
                                        <i class="fas fa-trash"></i> Eliminar
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/regions/index.blade.php ENDPATH**/ ?>