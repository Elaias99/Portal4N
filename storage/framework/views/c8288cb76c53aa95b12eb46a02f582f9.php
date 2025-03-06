

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="mb-4">Mis Peticiones</h1>

    <!-- Mostrar un mensaje de éxito si existe -->
    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <!-- Si no hay peticiones, mostrar un mensaje -->
    <?php if($peticiones->isEmpty()): ?>
        <p>No has realizado ninguna petición aún.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo de Petición</th>
                    <th>Estado</th>
                    <th>Fecha de Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $peticiones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $peticion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($peticion->id); ?></td>
                        <td><?php echo e($peticion->tipo_peticion); ?></td>
                        <td><?php echo e($peticion->estado); ?></td>
                        <td><?php echo e($peticion->created_at->format('d/m/Y')); ?></td>
                        <td>
                            <!-- Opciones para ver o gestionar la petición en el futuro -->
                            <a href="#" class="btn btn-info btn-sm">Ver</a>
                            
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                
            </tbody>
        </table>

    <?php endif; ?>
    <a href="<?php echo e(route('empleados.perfil')); ?>" class="btn btn-outline-primary btn-block">Atrás</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/perfiles/peticiones.blade.php ENDPATH**/ ?>