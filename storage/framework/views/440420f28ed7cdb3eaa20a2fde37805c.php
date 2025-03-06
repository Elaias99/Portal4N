

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="mb-4">Perfil del Empleado</h1>

    <!-- Contenedor de perfil de empleado -->
    <div class="row">
        <!-- Columna izquierda: Foto del empleado -->
        <div class="col-md-4">
            <div class="card p-3 mb-4">
                <?php if($trabajador->Foto): ?>
                    <img src="<?php echo e(url('storage/' . $trabajador->Foto)); ?>" class="img-fluid rounded-circle mb-3" alt="Foto de <?php echo e($trabajador->Nombre); ?>">
                <?php else: ?>
                    <img src="<?php echo e(url('images/default-avatar.png')); ?>" class="img-fluid rounded-circle mb-3" alt="Imagen predeterminada">
                <?php endif; ?>
                <button class="btn btn-outline-primary btn-block">Editar Perfil</button>
                <button class="btn btn-outline-secondary btn-block mt-2">Descargar PDF</button>
            </div>
        </div>

        <!-- Columna derecha: Información del empleado -->
        <div class="col-md-8">
            <div class="card p-4">
                <h4 class="mb-3"><i class="fas fa-user"></i> Información Personal</h4>
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th><i class="fas fa-id-card"></i> RUT</th>
                            <td><?php echo e($trabajador->Rut); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user"></i> Nombre Completo</th>
                            <td><?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->SegundoNombre); ?> <?php echo e($trabajador->TercerNombre); ?> <?php echo e($trabajador->ApellidoPaterno); ?> <?php echo e($trabajador->ApellidoMaterno); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Fecha de Nacimiento</th>
                            <td><?php echo e($trabajador->FechaNacimiento->format('d F, Y')); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-birthday-cake"></i> Edad</th>
                            <td><?php echo e($trabajador->edad); ?> años</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-phone"></i> Número Celular</th>
                            <td><?php echo e($trabajador->numero_celular); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user-friends"></i> Contacto de Emergencia</th>
                            <td><?php echo e($trabajador->nombre_emergencia); ?> | Número: <?php echo e($trabajador->contacto_emergencia); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-map-marker-alt"></i> Dirección</th>
                            <td><?php echo e($trabajador->calle); ?> <?php echo e($trabajador->comuna->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-envelope"></i> Correo</th>
                            <td><?php echo e($trabajador->Correo); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h4 class="mb-3 mt-4"><i class="fas fa-briefcase"></i> Información de Empleo</h4>
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th><i class="fas fa-building"></i> Empresa</th>
                            <td><?php echo e($trabajador->empresa->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-check"></i> Fecha de Contratación</th>
                            <td><?php echo e(\Carbon\Carbon::parse($trabajador->fecha_inicio_trabajo)->format('d F, Y')); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user-tie"></i> Cargo</th>
                            <td><?php echo e($trabajador->cargo->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-piggy-bank"></i> AFP</th>
                            <td><?php echo e($trabajador->afp->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-heartbeat"></i> Salud</th>
                            <td><?php echo e($trabajador->salud->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-coins"></i> Salario Bruto</th>
                            <td><?php echo e($trabajador->salario_bruto > 0 ? '$' . number_format($trabajador->salario_bruto, 0, ',', '.') : 'No asignado'); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-ring"></i> Estado Civil</th>
                            <td><?php echo e($trabajador->estadoCivil->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-check-circle"></i> Contrato Firmado</th>
                            <td><?php echo e($trabajador->ContratoFirmado); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-file-signature"></i> Anexo Contrato</th>
                            <td><?php echo e($trabajador->AnexoContrato); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clipboard-list"></i> Situación</th>
                            <td><?php echo e($trabajador->situacion->Nombre); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock"></i> Turno</th>
                            <td><?php echo e($trabajador->turno ? $trabajador->turno->nombre : 'No asignado'); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Sistema de Trabajo</th>
                            <td><?php echo e($trabajador->sistemaTrabajo ? $trabajador->sistemaTrabajo->nombre : 'No asignado'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Espacio reservado para futuras secciones -->
                <div class="alert alert-secondary mt-4" role="alert">
                    <i class="fas fa-info-circle"></i> Próximamente: Secciones como Vacaciones y Días Trabajados estarán disponibles.
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/empleados/perfil.blade.php ENDPATH**/ ?>