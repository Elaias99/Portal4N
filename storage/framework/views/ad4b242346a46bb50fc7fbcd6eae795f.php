

<?php $__env->startSection('content'); ?>

<div class="container">
    <?php if(Session::has('Mensaje')): ?>
        <div class="alert alert-success" role="alert">
            <?php echo e(Session::get('Mensaje')); ?>

        </div>
    <?php endif; ?>

    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Hijos</h1>

    <div class="row">
        <?php $__currentLoopData = $trabajadores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trabajador): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($trabajador->hijos->isNotEmpty()): ?> <!-- Mostrar solo si el trabajador tiene hijos -->
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->ApellidoPaterno); ?> <?php echo e($trabajador->ApellidoMaterno); ?></h5>
                            <p class="card-text">
                                <?php echo e($trabajador->hijos->count()); ?> hijo(s)
                                <br>
                                <?php $__currentLoopData = $trabajador->hijos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hijo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <small><?php echo e($hijo->nombre); ?> (<?php echo e($hijo->edad); ?> años)</small><br>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </p>
                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#detailsModal<?php echo e($trabajador->id); ?>">Ver Detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="detailsModal<?php echo e($trabajador->id); ?>" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel<?php echo e($trabajador->id); ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailsModalLabel<?php echo e($trabajador->id); ?>">Hijos de <?php echo e($trabajador->Nombre); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Género</th>
                                            <th>Parentesco</th>
                                            <th>Fecha de Nacimiento</th>
                                            <th>Edad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $trabajador->hijos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hijo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e($hijo->nombre); ?></td>
                                                <td><?php echo e($hijo->genero); ?></td>
                                                <td><?php echo e($hijo->parentesco); ?></td>
                                                <td><?php echo e($hijo->fecha_nacimiento); ?></td>
                                                <td><?php echo e($hijo->edad); ?></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/hijos/index.blade.php ENDPATH**/ ?>