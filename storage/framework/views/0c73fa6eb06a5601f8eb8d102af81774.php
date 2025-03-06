

<?php $__env->startSection('content'); ?>
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Lista de AFPs</h1>
        <a href="<?php echo e(route('afps.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear AFP
        </a>
    </div>

    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tasa de Cotización (%)</th>
                    <th>Tasa SIS (%)</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $afps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $afp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($afp->Nombre); ?></td>
                    <td><?php echo e(number_format($afp->tasaAfp->tasa_cotizacion ?? 0, 2, ',', '.')); ?>%</td>
                    <td><?php echo e(number_format($afp->tasaAfp->tasa_sis ?? 0, 2, ',', '.')); ?>%</td>

                    <td class="text-center">
                        <a href="<?php echo e(route('afps.edit', $afp->id)); ?>" class="btn btn-sm btn-warning" aria-label="Editar AFP <?php echo e($afp->Nombre); ?>">
                            <i class="fas fa-edit"></i> Editar
                        </a>

                        <form action="<?php echo e(route('afps.destroy', $afp->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" aria-label="Eliminar AFP <?php echo e($afp->Nombre); ?>" onclick="return confirm('¿Seguro que deseas eliminar esta AFP?');">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <footer class="mt-4 text-center">
        <a href="https://www.previred.com/indicadores-previsionales/" target="_blank" class="text-info">
            <i class="fa-solid fa-info fa-2x"></i>
            <p>Indicadores Previsionales</p>
        </a>
    </footer>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/afps/index.blade.php ENDPATH**/ ?>