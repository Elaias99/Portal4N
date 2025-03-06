

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="mb-4">Mis Solicitudes</h1>

    <!-- Sección para solicitudes de modificación -->
    <h2>Modificación</h2>
    <?php
    $modificacionCampos = [
        'afp', 
        'cargo', 
        'salario_bruto', 
        'fecha_ingreso', 
        'fecha_inicio_contrato',
        'banco',
        'numero_cuenta',
        'tipo_cuenta',
        'estado_civil',
        'sistema_trabajo',
        'turno',
        'situacion',
        'comuna',
        'contrato_firmado',
        'anexo_contrato'
    ]; // Lista actualizada de campos
?>
    <?php if($solicitudes->whereIn('campo', $modificacionCampos)->isEmpty()): ?>
        <p>No tienes solicitudes de modificación.</p>
    <?php else: ?>
        <div class="row">
            <?php $__currentLoopData = $solicitudes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solicitud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(in_array($solicitud->campo, $modificacionCampos)): ?> <!-- Solo mostrar solicitudes de modificación -->
                <div class="col-md-4">
                    <div class="card mb-3 <?php if($solicitud->estado == 'aprobado'): ?> border-success 
                                            <?php elseif($solicitud->estado == 'rechazado'): ?> border-danger 
                                            <?php else: ?> border-warning <?php endif; ?>">
                        <div class="card-body">
                            <h5 class="card-title">Campo Solicitado: <?php echo e(ucfirst($solicitud->campo)); ?></h5>
                            
                            <!-- Mostrar comentario del administrador en lugar de descripción -->
                            <p><strong>Comentario del Administrador:</strong> <?php echo e(Str::limit($solicitud->comentario_admin ?? 'Sin comentario', 50)); ?></p>

                            <p><strong>Estado:</strong> 
                                <span class="<?php if($solicitud->estado == 'aprobado'): ?> text-success 
                                            <?php elseif($solicitud->estado == 'rechazado'): ?> text-danger 
                                            <?php else: ?> text-warning <?php endif; ?>">
                                    <?php echo e(ucfirst($solicitud->estado)); ?>

                                </span>
                            </p>

                            <!-- Botón Ver detalles -->
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#solicitudModal<?php echo e($solicitud->id); ?>">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Incluir el modal -->
                <?php echo $__env->make('partials.solicitud_modal', ['solicitud' => $solicitud], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <!-- Sección para solicitudes de días (vacaciones) -->
    <!-- Sección para solicitudes de días (vacaciones) -->
    <h2>Días</h2>
<?php if($solicitudes->where('campo', 'Vacaciones')->isEmpty()): ?>
    <p>No tienes solicitudes de días (vacaciones).</p>
<?php else: ?>
    <div class="row">
        <?php $__currentLoopData = $solicitudes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solicitud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($solicitud->campo == 'Vacaciones'): ?>
            <div class="col-md-4">
                <div class="card mb-3 <?php if($solicitud->estado == 'aprobado'): ?> border-success 
                                        <?php elseif($solicitud->estado == 'rechazado'): ?> border-danger 
                                        <?php else: ?> border-warning <?php endif; ?>">
                    <div class="card-body">
                        <h5 class="card-title">Solicitud de <?php echo e($solicitud->tipo_dia === 'vacaciones' ? 'Vacaciones' : 
                            ($solicitud->tipo_dia === 'administrativo' ? 'Día Administrativo' : 
                            ($solicitud->tipo_dia === 'sin_goce_de_sueldo' ? 'Permiso sin goce de sueldo' : 
                            ($solicitud->tipo_dia === 'permiso_fuerza_mayor' ? 'Permiso fuerza mayor' : 
                            'Licencia Médica')))); ?></h5>
                        
                        
                        
                        <?php if($solicitud->vacacion): ?>
                            <p><strong>Fecha Inicio:</strong> <?php echo e($solicitud->vacacion->fecha_inicio->format('Y-m-d')); ?></p>
                            <p><strong>Fecha Fin:</strong> <?php echo e($solicitud->vacacion->fecha_fin->format('Y-m-d')); ?></p>
                        <?php else: ?>
                            <p>No hay detalles de fechas para esta solicitud.</p>
                        <?php endif; ?>
                        
                        <p><strong>Comentario del Administrador:</strong> <?php echo e(Str::limit($solicitud->comentario_admin ?? 'Sin comentario', 50)); ?></p>
                        
                        <p><strong>Estado:</strong> 
                            <span class="<?php if($solicitud->estado == 'aprobado'): ?> text-success 
                                        <?php elseif($solicitud->estado == 'rechazado'): ?> text-danger 
                                        <?php else: ?> text-warning <?php endif; ?>">
                                <?php echo e(ucfirst($solicitud->estado)); ?>

                            </span>
                        </p>
                        
                        <!-- Botón Ver detalles -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#solicitudModal<?php echo e($solicitud->id); ?>">
                            Ver Detalles
                        </button>
                        
                        <!-- Enlace para descargar el PDF generado automáticamente, solo si la solicitud está aprobada y el PDF existe -->
                        <?php if($solicitud->estado === 'aprobado' && $solicitud->vacacion && $solicitud->vacacion->archivo_admin): ?>
                            <a href="<?php echo e(route('vacaciones.descargarArchivoAdmin', $solicitud->vacacion->id)); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-file-pdf"></i> Descargar PDF de solicitud de días
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Incluir el modal -->
            <?php echo $__env->make('partials.solicitud_modal', ['solicitud' => $solicitud], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>



    <!-- Botón para volver -->
    <a href="<?php echo e(route('empleados.perfil')); ?>" class="btn btn-primary mt-4">Volver</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/perfiles/solicitudes.blade.php ENDPATH**/ ?>