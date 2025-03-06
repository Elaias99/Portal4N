

<?php $__env->startSection('content'); ?>

<!-- Estilos personalizados para tarjetas -->
<style>
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
    }
    .card-title {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .card-body {
        padding: 15px;
    }
    .btn {
        border-radius: 30px;
        padding: 8px 16px;
        font-size: 0.9rem;
        transition: background-color 0.3s ease;
    }
    .btn-success:hover {
        background-color: #28a745;
    }
    .btn-danger:hover {
        background-color: #dc3545;
    }
</style>

<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Solicitudes de Días</h1>

    <!-- Filtros por estado -->
    <form action="<?php echo e(route('solicitudes.vacaciones')); ?>" method="GET" class="mb-3">
        <div class="input-group">
            <select name="estado" id="estado" class="form-control">
                <option value="">Todos</option>
                <option value="pendiente" <?php echo e(request('estado') == 'pendiente' ? 'selected' : ''); ?>>Pendientes</option>
                <option value="aprobado" <?php echo e(request('estado') == 'aprobado' ? 'selected' : ''); ?>>Aprobadas</option>
                <option value="rechazado" <?php echo e(request('estado') == 'rechazado' ? 'selected' : ''); ?>>Rechazadas</option>
            </select>
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Aplicar Filtro</button>
            </div>
        </div>
    </form>

    <!-- Mostrar mensajes de éxito y advertencia -->
    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('warning')): ?>
        <div class="alert alert-warning">
            <?php echo e(session('warning')); ?>

        </div>
    <?php endif; ?>

    <!-- Tarjetas de solicitudes -->
    <div class="row">
        <?php $__currentLoopData = $solicitudes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solicitud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($solicitud->campo == 'Vacaciones'): ?> <!-- Mostrar solo solicitudes de vacaciones -->
            <div class="col-md-4">
                <div class="card <?php if($solicitud->estado === 'aprobado'): ?> border-success 
                                <?php elseif($solicitud->estado === 'rechazado'): ?> border-danger 
                                <?php else: ?> border-warning <?php endif; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo e($solicitud->trabajador->Nombre); ?> <?php echo e($solicitud->trabajador->ApellidoPaterno); ?></h5>
                        <p><strong>Tipo de Día:</strong> <?php echo e(ucfirst($solicitud->tipo_dia)); ?></p>

                        <p><strong>Descripción:</strong> <?php echo e(Str::limit($solicitud->descripcion, 60)); ?></p>
                        
                        <!-- Mostrar fechas relevantes -->
                        <p><strong>Fecha Inicio:</strong> <?php echo e($solicitud->vacacion->fecha_inicio->format('Y-m-d')); ?></p>
                        <p><strong>Fecha Fin:</strong> <?php echo e($solicitud->vacacion->fecha_fin->format('Y-m-d')); ?></p>
                        
                        <!-- Días Solicitados y Días Descontados -->
                        <p><strong>Días Solicitados:</strong> <?php echo e($solicitud->vacacion->dias); ?></p>

                        <?php if($solicitud->tipo_dia === 'vacaciones'): ?>
                            <p><strong>Días Descontados:</strong> <?php echo e($solicitud->vacacion->dias); ?></p>
                        <?php else: ?>
                            <p><strong>Días Descontados:</strong> 0</p>
                        <?php endif; ?>

                        <!-- Mostrar archivo adjunto si existe -->
                        <?php if($solicitud->vacacion->archivo): ?>
                            <p><strong>Archivo Adjunto:</strong> 
                                <a href="<?php echo e(route('vacaciones.descargar', $solicitud->vacacion->id)); ?>" target="_blank">Descargar Archivo</a>
                            </p>
                        <?php endif; ?>




                        <p><strong>Estado:</strong> <?php echo e(ucfirst($solicitud->estado)); ?></p>

                        <!-- Formularios de aprobación y rechazo con comentario -->
                        <div class="mt-3">
                            <!-- Formulario de aprobación, visible solo si el estado es 'pendiente' -->
                            <?php if($solicitud->estado === 'pendiente'): ?>
                                <form action="<?php echo e(route('solicitudes.vacaciones.approve', $solicitud->id)); ?>" method="POST" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="comentario_admin">Comentario del Administrador</label>
                                        <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Aprobar</button>
                                </form>

                                <form action="<?php echo e(route('solicitudes.vacaciones.reject', $solicitud->id)); ?>" method="POST" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <label for="comentario_admin">Comentario del Administrador</label>
                                        <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Rechazar</button>
                                </form>
                            <?php endif; ?>

                            <!-- Campo para subir archivo de respaldo, solo visible cuando el estado es 'aprobado' o 'rechazado' y no existe ya un archivo de respaldo -->
                            <?php if(($solicitud->estado === 'aprobado' || $solicitud->estado === 'rechazado') && is_null($solicitud->vacacion->archivo_respuesta_admin)): ?>
                                <form action="<?php echo e(route('solicitudes.vacaciones.approve', $solicitud->id)); ?>" method="POST" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">

                                        <label for="archivo_respuesta_admin">Subir archivo escaneado y firmado</label>
                                        <input type="file" name="archivo_respuesta_admin" class="form-control" id="archivo_respuesta_admin" required>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Subir Respaldo</button>

                                    <?php if($solicitud->estado === 'aprobado' && $solicitud->vacacion && $solicitud->vacacion->archivo_admin): ?>
                                        <a href="<?php echo e(route('vacaciones.descargarArchivoAdmin', $solicitud->vacacion->id)); ?>" class="btn btn-sm btn-outline-danger mt-2">
                                            <i class="fa-solid fa-file-pdf"></i> Descargar PDF de solicitud de días
                                        </a>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    
    <!-- Botón de regreso -->
    <a href="<?php echo e(url('/empleados')); ?>" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/solicitudes/vacaciones.blade.php ENDPATH**/ ?>