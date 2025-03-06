
 

 <?php $__env->startSection('content'); ?>
 
 <script>
 
     .border-success {
         border: 2px solid #28a745 !important; /* Verde */
     }
 
     .border-danger {
         border: 2px solid #dc3545 !important; /* Rojo */
     }
 
     .border-secondary {
         border: 2px solid #6c757d !important; /* Gris */
     }
 
 </script>
 
 <div class="container">
     <h1 class="text-center mb-4">Solicitudes de Modificación</h1>
 
     <!-- Filtros por estado -->
     <form action="<?php echo e(route('solicitudes.index')); ?>" method="GET" class="mb-3">
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
 
     <div class="row">
         <?php $__currentLoopData = $solicitudes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solicitud): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
             <?php if($solicitud->campo != 'Vacaciones'): ?> 
             <div class="col-md-3">
                 <!-- Aplicar la clase condicional según el estado -->
                 <div class="card mb-3 shadow-sm position-relative <?php if($solicitud->estado === 'aprobado'): ?> border-success 
                                 <?php elseif($solicitud->estado === 'rechazado'): ?> border-danger 
                                 <?php else: ?> border-secondary <?php endif; ?>" 
                     style="border-radius: 10px;">
                     <div class="card-body p-3 text-center">
                         <h5 class="card-title mb-2"><?php echo e($solicitud->trabajador->Nombre); ?> <?php echo e($solicitud->trabajador->ApellidoPaterno); ?></h5>
     
                         <!-- Información del campo solicitado y descripción -->
                         <p><strong>Campo Solicitado:</strong> <?php echo e(ucfirst($solicitud->campo)); ?></p>
                         <p><strong>Descripción:</strong> <?php echo e(Str::limit($solicitud->descripcion, 40)); ?></p>
     
                         <!-- Estado con clase de estilo personalizada -->
                         <p><strong>Estado:</strong> <span class="badge-estado"><?php echo e(ucfirst($solicitud->estado)); ?></span></p>
     
                         <!-- Archivo adjunto con un icono simple -->
                         <?php if($solicitud->archivo): ?>
                         <p><strong>Archivo:</strong> 
                             <a href="<?php echo e(route('solicitudes.descargar', $solicitud->id)); ?>" class="text-secondary">
                                 <i class="fa-solid fa-file-arrow-down"></i> Descargar
                             </a>
                         </p>
                         <?php endif; ?>
     
                         <!-- Formularios de aprobar y rechazar con comentario -->
                         <div class="d-flex flex-column gap-2 mt-3">
                             <!-- Solo mostrar el formulario si no hay archivo de respaldo subido -->
                             <?php if(is_null($solicitud->archivo_admin)): ?>
                                 <!-- Formulario de aprobación -->
                                 <form action="<?php echo e(route('solicitudes.approve', $solicitud->id)); ?>" method="POST" enctype="multipart/form-data">
                                     <?php echo csrf_field(); ?>
                                     
                                     <!-- Campo de comentario solo es requerido si la solicitud está en estado pendiente -->
                                     <?php if($solicitud->estado === 'pendiente'): ?>
                                         <div class="form-group">
                                             <label for="comentario_admin">Comentario del Administrador</label>
                                             <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                         </div>
                                     <?php endif; ?>
                                     
                                     <!-- Campo para adjuntar archivo (siempre disponible si no hay archivo_admin) -->
                                     <div class="form-group">
                                         <label for="archivo_admin">Adjuntar archivo (opcional):</label>
                                         <input type="file" class="form-control" name="archivo_admin" id="archivo_admin" <?php if($solicitud->estado === 'aprobado'): ?> required <?php endif; ?>>
                                     </div>
                                 
                                     <button type="submit" class="btn btn-sm btn-outline-success mt-2">
                                         <?php echo e($solicitud->estado === 'pendiente' ? 'Aprobar' : 'Subir Respaldo'); ?>

                                     </button>
                                 </form>
                             <?php else: ?>
                                 <p class="text-muted">El archivo de respaldo ya fue subido.</p>
                             <?php endif; ?>
 
                             <!-- Formulario de rechazo (si está en pendiente) -->
                             <?php if($solicitud->estado === 'pendiente'): ?>
                                 <form action="<?php echo e(route('solicitudes.reject', $solicitud->id)); ?>" method="POST" enctype="multipart/form-data">
                                     <?php echo csrf_field(); ?>
                                     <!-- Campo de comentario del administrador -->
                                     <div class="form-group">
                                         <label for="comentario_admin">Comentario del Administrador</label>
                                         <textarea name="comentario_admin" id="comentario_admin" class="form-control" rows="2" required></textarea>
                                     </div>
                                 
                                     <!-- Campo para adjuntar archivo -->
                                     <div class="form-group">
                                         <label for="archivo_admin">Adjuntar archivo (opcional):</label>
                                         <input type="file" class="form-control" name="archivo_admin" id="archivo_admin">
                                     </div>
                                 
                                     <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Rechazar</button>
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
     <div class="text-center mt-4">
         <a href="<?php echo e(url('/empleados')); ?>" class="btn btn-primary">
             <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
         </a>
     </div>
 </div>
 <?php $__env->stopSection(); ?>
 
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/solicitudes/index.blade.php ENDPATH**/ ?>