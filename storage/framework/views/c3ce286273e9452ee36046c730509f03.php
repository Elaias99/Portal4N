

<?php $__env->startSection('content'); ?>
<div class="container mt-5">

    <!-- Mostrar mensaje de éxito o error si existe -->
    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <?php if(!isset($trabajador)): ?>
        <!-- Formulario para seleccionar un trabajador -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3>Seleccionar Trabajador</h3>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('historial-vacacion.index')); ?>" method="GET">
                    <div class="form-group">
                        <label for="trabajador_id">Trabajador</label>
                        <select name="trabajador_id" class="form-control" required>
                            <option value="" disabled selected>Seleccione un trabajador</option>
                            <?php $__currentLoopData = $trabajadores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trabajador): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($trabajador->id); ?>"><?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->ApellidoPaterno); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Ver Historial</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Historial del trabajador seleccionado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center">Historial de Solicitudes de Días de <?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->ApellidoPaterno); ?></h1>
            <a href="<?php echo e(route('historial-vacacion.create')); ?>" class="btn btn-primary mt-3">Registrar Días Históricos</a>
        </div>

        <!-- Tabla de Historial de Vacaciones -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3>Vacaciones Históricas</h3>
            </div>
            <div class="card-body">
                <?php if($historialVacaciones->isEmpty()): ?>
                    <p class="text-center">No hay registros históricos de vacaciones.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días Tomados</th>
                                <th>Días Descontados</th>
                                <th>Tipo de Día</th>
                                <th>Archivo Respaldo</th> <!-- Nueva columna -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $historialVacaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vacacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($vacacion->id); ?></td>
                                    <td><?php echo e($vacacion->fecha_inicio->format('Y-m-d')); ?></td>
                                    <td><?php echo e($vacacion->fecha_fin->format('Y-m-d')); ?></td>
                                    <td><?php echo e($vacacion->dias_laborales); ?></td>
                                    <td><?php echo e($vacacion->dias_descontados); ?></td>
                                    <td><?php echo e(ucfirst($vacacion->tipo_dia)); ?></td>

                                    <td>
                                        <?php if($vacacion->archivo_respaldo): ?>
                                            <a href="<?php echo e(route('historial-vacacion.descargar', $vacacion->id)); ?>" class="btn btn-sm btn-outline-primary">
                                                Descargar PDF
                                            </a>
                                        <?php else: ?>
                                            <!-- Formulario para subir archivo -->
                                            <form action="<?php echo e(route('historial-vacacion.subir', $vacacion->id)); ?>" method="POST" enctype="multipart/form-data">
                                                <?php echo csrf_field(); ?>
                                                <input type="file" name="archivo_respaldo" class="form-control mb-2" required>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Subir Archivo</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    
                                    
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de Solicitudes Aprobadas Recientes -->
        <!-- Tabla de Solicitudes Aprobadas Recientes -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3>Solicitudes Aprobadas Recientes</h3>
            </div>
            <div class="card-body">
                <?php if($solicitudesAprobadas->isEmpty()): ?>
                    <p class="text-center">No hay solicitudes de vacaciones aprobadas.</p>
                <?php else: ?>
                <div class="table-responsive">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Días Tomados</th> <!-- Nueva columna para los días tomados -->
                                <th>Días Descontados</th> <!-- Nueva columna para los días descontados -->
                                <th>Tipo de Día</th>
                                <th>Comentario del Administrador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $solicitudesAprobadas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solicitud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($solicitud->vacacion->fecha_inicio->format('Y-m-d')); ?></td>
                                    <td><?php echo e($solicitud->vacacion->fecha_fin->format('Y-m-d')); ?></td>
                                    <td><?php echo e($solicitud->dias_tomados); ?></td> <!-- Mostrar los días tomados -->
                                    <td><?php echo e($solicitud->dias_descontados); ?></td> <!-- Mostrar los días descontados -->
                                    <td><?php echo e(ucfirst($solicitud->tipo_dia)); ?></td> <!-- Mostrar el tipo de día -->
                                    <td><?php echo e($solicitud->comentario_admin ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>






                </div>

                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo e(url('/historial-vacacion')); ?>" class="btn btn-primary mt-3">
            <i class="fas fa-arrow-left"></i> Regresar a la selección
        </a>


        
    <?php endif; ?>
    <a href="<?php echo e(url('/empleados')); ?>" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/historial_vacacion/index.blade.php ENDPATH**/ ?>