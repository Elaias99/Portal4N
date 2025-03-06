

<?php $__env->startSection('content'); ?>
<style>
    /* Estilos CSS integrados */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f7f9fc;
        color: #333;
    }

    .modern-title {
        font-size: 2rem;
        margin-bottom: 1.5rem;
        color: #444;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .modern-card {
        border-radius: 16px;
        padding: 1.5rem;
        background-color: #fff;
    }

    .modern-card-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .modern-icon {
        font-size: 1.5rem;
        color: #555;
        margin-right: 0.75rem;
    }

    .modern-button {
        background-color: #007bff;
        color: white;
        border-radius: 8px;
        padding: 0.75rem;
        transition: background-color 0.3s ease;
    }

    .modern-button:hover {
        background-color: #0056b3;
    }
</style>

<div class="container">
    <h1 class="text-center modern-title">Lista de Tallas</h1>

    <div class="row">
        <?php $__currentLoopData = $tallas->groupBy('trabajador.id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trabajadorId => $tallasPorTrabajador): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $trabajador = $tallasPorTrabajador->first()->trabajador; ?>

            <div class="col-md-4 mb-4">
                <div class="card h-100 modern-card shadow-lg">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title modern-card-title"><?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->ApellidoPaterno); ?> <?php echo e($trabajador->ApellidoMaterno); ?></h5>
                        
                        <div class="mb-3">
                            <strong>Tallas Principales:</strong>
                            <div class="d-flex align-items-center mt-2">
                                <?php $__currentLoopData = $tallasPorTrabajador; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $talla): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($talla->tipoVestimenta->Nombre == 'Polera'): ?>
                                        <i class="fas fa-tshirt modern-icon"></i>
                                    <?php elseif($talla->tipoVestimenta->Nombre == 'Pantalón'): ?>
                                        <i class="fa-solid fa-vest-patches modern-icon"></i>
                                    <?php elseif($talla->tipoVestimenta->Nombre == 'Calzado'): ?>
                                        <i class="fa-solid fa-shoe-prints modern-icon"></i>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <button type="button" class="btn modern-button btn-block" data-toggle="modal" data-target="#detailsModal<?php echo e($trabajador->id); ?>">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<!-- Modales -->
<?php $__currentLoopData = $tallas->groupBy('trabajador.id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trabajadorId => $tallasPorTrabajador): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $trabajador = $tallasPorTrabajador->first()->trabajador; ?>
    <div class="modal fade" id="detailsModal<?php echo e($trabajador->id); ?>" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel<?php echo e($trabajador->id); ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel<?php echo e($trabajador->id); ?>">Detalles de Tallas de <?php echo e($trabajador->Nombre); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <strong><h5 class="modal-title">Tallas de <?php echo e($trabajador->Nombre); ?></h5></strong><br><br>
                    <?php $__currentLoopData = $tallasPorTrabajador; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $talla): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo e($talla->tipoVestimenta->Nombre); ?>: <?php echo e($talla->talla); ?><br>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/tallas/index.blade.php ENDPATH**/ ?>