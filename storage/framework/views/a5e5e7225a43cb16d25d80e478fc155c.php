

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>Archivos de Respaldo Adjuntados por el Administrador</h1>

    <div class="row">
        <?php $__currentLoopData = $empleadosConRespaldo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trabajador_id => $respaldo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-3">
            <div class="card mb-3 shadow-sm position-relative" style="border-radius: 10px;">
                <div class="card-body p-2">
                    <div class="text-center mb-2">
                        <h5 class="card-title mb-1">
                            <?php if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty()): ?>
                                <?php echo e($respaldo['vacaciones']->first()->trabajador->Nombre); ?> 
                                <?php echo e($respaldo['vacaciones']->first()->trabajador->ApellidoPaterno); ?>

                            <?php elseif(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty()): ?>
                                <?php echo e($respaldo['modificaciones']->first()->trabajador->Nombre); ?> 
                                <?php echo e($respaldo['modificaciones']->first()->trabajador->ApellidoPaterno); ?>

                            <?php endif; ?>
                        </h5>
                        <small class="text-muted">
                            <?php if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty()): ?>
                                <?php echo e($respaldo['vacaciones']->first()->trabajador->cargo->Nombre); ?>

                            <?php elseif(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty()): ?>
                                <?php echo e($respaldo['modificaciones']->first()->trabajador->cargo->Nombre); ?>

                            <?php endif; ?>
                        </small>
                    </div>

                    <div class="text-center mb-2">
                        <?php if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty() && $respaldo['vacaciones']->first()->trabajador->empresa && $respaldo['vacaciones']->first()->trabajador->empresa->logo): ?>
                            <img src="<?php echo e(asset('storage/' . $respaldo['vacaciones']->first()->trabajador->empresa->logo)); ?>" alt="Logo de <?php echo e($respaldo['vacaciones']->first()->trabajador->empresa->Nombre); ?>" style="max-height: 50px;">
                        <?php elseif(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty() && $respaldo['modificaciones']->first()->trabajador->empresa && $respaldo['modificaciones']->first()->trabajador->empresa->logo): ?>
                            <img src="<?php echo e(asset('storage/' . $respaldo['modificaciones']->first()->trabajador->empresa->logo)); ?>" alt="Logo de <?php echo e($respaldo['modificaciones']->first()->trabajador->empresa->Nombre); ?>" style="max-height: 50px;">
                        <?php else: ?>
                            <p class="text-muted">No hay logo disponible</p>
                        <?php endif; ?>
                    </div>

                    <!-- Botón para desplegar opciones de archivos de respaldo -->
                    <div class="text-center mt-3">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo e($trabajador_id); ?>" data-toggle="dropdown" aria-expanded="false">
                                Ver Archivos de Respaldo
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton<?php echo e($trabajador_id); ?>">
                                <?php if(isset($respaldo['vacaciones']) && $respaldo['vacaciones']->isNotEmpty()): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalVacaciones<?php echo e($trabajador_id); ?>">Archivos de Respaldo de Vacaciones</a>
                                    </li>
                                <?php endif; ?>
                                <?php if(isset($respaldo['modificaciones']) && $respaldo['modificaciones']->isNotEmpty()): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalModificaciones<?php echo e($trabajador_id); ?>">Archivos de Respaldo de Modificaciones</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Archivos de Respaldo de Vacaciones -->
        <div class="modal fade" id="modalVacaciones<?php echo e($trabajador_id); ?>" tabindex="-1" role="dialog" aria-labelledby="modalVacacionesLabel<?php echo e($trabajador_id); ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalVacacionesLabel<?php echo e($trabajador_id); ?>">Archivos de Respaldo de Vacaciones</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha de Solicitud</th>
                                    <th>Hora de Envío</th> 
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Tipo de Día</th>
                                    <th>Estado</th>
                                    <th>Hora de Respuesta</th>
                                    <th>Comentario del Administrador</th>
                                    <th>Archivo de Respaldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(isset($respaldo['vacaciones'])): ?>
                                    <?php $__currentLoopData = $respaldo['vacaciones']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vacacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($vacacion->solicitud->created_at->format('d-m-Y')); ?></td>
                                            <td><?php echo e($vacacion->solicitud->created_at->format('H:i:s')); ?></td>
                                            <td><?php echo e($vacacion->fecha_inicio->format('Y-m-d')); ?></td>
                                            <td><?php echo e($vacacion->fecha_fin->format('Y-m-d')); ?></td>
                                            <td><?php echo e($vacacion->solicitud->tipo_dia); ?></td>
                                            <td><?php echo e(ucfirst($vacacion->solicitud->estado)); ?></td>
                                            <td><?php echo e($vacacion->solicitud->updated_at->format('H:i:s')); ?></td>
                                            <td><?php echo e($vacacion->solicitud->comentario_admin ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if($vacacion->archivo_respuesta_admin): ?>
                                                    <a href="<?php echo e(route('vacaciones.descargarArchivoRespuestaAdmin', $vacacion->id)); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                        Descargar Respaldo
                                                    </a>
                                                <?php else: ?>
                                                    No disponible
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">No hay archivos de respaldo de vacaciones disponibles.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Archivos de Respaldo de Modificaciones -->
        <div class="modal fade" id="modalModificaciones<?php echo e($trabajador_id); ?>" tabindex="-1" role="dialog" aria-labelledby="modalModificacionesLabel<?php echo e($trabajador_id); ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalModificacionesLabel<?php echo e($trabajador_id); ?>">Archivos de Respaldo de Modificaciones</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha de Solicitud</th>

                                    <th>Hora de Envío</th>
                                    <th>Descripción</th>

                                    <th>Estado</th>

                                    <th>Hora de Respuesta</th>

                                    <th>Comentario del Administrador</th>
                                    <th>Archivo de Respaldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(isset($respaldo['modificaciones'])): ?>
                                    <?php $__currentLoopData = $respaldo['modificaciones']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modificacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($modificacion->created_at->format('d-m-Y')); ?></td>

                                            <td><?php echo e($modificacion->created_at->format('H:i:s')); ?></td>

                                            <td><?php echo e($modificacion->descripcion ?? 'Modificación de datos'); ?></td>
                                            <td><?php echo e(ucfirst($modificacion->estado)); ?></td>
                                            <td><?php echo e($modificacion->updated_at->format('H:i:s')); ?></td>
                                            <td><?php echo e($modificacion->comentario_admin ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if($modificacion->archivo_admin): ?>
                                                    <a href="<?php echo e(route('solicitudes.descargar-archivo-admin', $modificacion->id)); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                        Descargar Respaldo
                                                    </a>
                                                <?php else: ?>
                                                    No disponible
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">No hay archivos de respaldo de modificaciones disponibles.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <a href="<?php echo e(url('/empleados')); ?>" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
    </a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/admin/archivos_respaldo.blade.php ENDPATH**/ ?>