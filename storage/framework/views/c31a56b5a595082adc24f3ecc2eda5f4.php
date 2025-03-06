<!-- Modal para ver detalles de la solicitud -->
<div class="modal fade" id="solicitudModal<?php echo e($solicitud->id); ?>" tabindex="-1" role="dialog" aria-labelledby="solicitudModalLabel<?php echo e($solicitud->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="solicitudModalLabel<?php echo e($solicitud->id); ?>">
                    Detalles de la Solicitud #<?php echo e($solicitud->id); ?>

                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Contenido Detallado de la Solicitud -->
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>Tipo de Día Solicitado</th>
                            <td><?php echo e(ucfirst($solicitud->tipo_dia)); ?></td> <!-- Mostrar dinámicamente el tipo de día -->
                        </tr>
                        <tr>
                            <th>Cantidad de Días Solicitados</th>
                            <td><?php echo e($solicitud->vacacion->dias ?? 'No especificado'); ?></td> <!-- Mostrar cantidad de días -->
                        </tr>
                        <tr>
                            <th>Comentario del Administrador</th>
                            <td><?php echo e($solicitud->comentario_admin ?? 'Sin comentarios aún'); ?></td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                <span class="<?php if($solicitud->estado === 'aprobado'): ?> text-success
                                             <?php elseif($solicitud->estado === 'rechazado'): ?> text-danger
                                             <?php else: ?> text-warning <?php endif; ?>">
                                    <?php echo e(ucfirst($solicitud->estado)); ?>

                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Fecha de Solicitud</th>
                            <td><?php echo e($solicitud->created_at); ?></td>
                        </tr>
                        <tr>
                            <th>Fecha de Respuesta</th>
                            <td><?php echo e($solicitud->fecha_respuesta ? $solicitud->fecha_respuesta : 'Pendiente'); ?></td>
                        </tr>
                        <?php if($solicitud->vacacion): ?>
                        <tr>
                            <th>Fecha de Inicio</th>
                            <td><?php echo e($solicitud->vacacion->fecha_inicio->format('Y-m-d')); ?></td>
                        </tr>
                        <tr>
                            <th>Fecha de Fin</th>
                            <td><?php echo e($solicitud->vacacion->fecha_fin->format('Y-m-d')); ?></td>
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
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/partials/solicitud_modal.blade.php ENDPATH**/ ?>