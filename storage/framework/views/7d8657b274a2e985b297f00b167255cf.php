

<?php echo app('Illuminate\Foundation\Vite')(['resources/css/custom.css']); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('warning')): ?>
    <div class="alert alert-warning">
        <?php echo e(session('warning')); ?>

    </div>
<?php endif; ?>

<?php if(session('success')): ?>
    <div class="alert alert-success">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<?php if(Auth::user()->unreadNotifications->count() > 0): ?>
    <div class="container mb-4">
        <h4 class="mb-3">Notificaciones</h4>
        <div class="list-group">
            <?php $__currentLoopData = Auth::user()->unreadNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="list-group-item d-flex justify-content-between align-items-center shadow-sm p-3 mb-3 bg-light rounded">
                    <!-- Icono y mensaje de notificación -->
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-info-circle fa-lg text-info me-3"></i>
                        <span><?php echo e($notification->data['mensaje']); ?></span>
                    </div>

                    <!-- Acciones -->
                    <div class="d-flex align-items-center">
                        <a href="<?php echo e(route('perfiles.solicitudes')); ?>" class="btn btn-sm btn-outline-primary me-2">
                            Ver solicitudes
                        </a>
                        <a href="<?php echo e(route('notifications.markAsRead', $notification->id)); ?>" class="btn btn-sm btn-primary">
                            Marcar como leída
                        </a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <p class="alert alert-info">No tienes notificaciones nuevas.</p>
    </div>
<?php endif; ?>

<div class="container">
    <h1 class="mb-4 text-center">Perfil del Empleado</h1>

    <!-- Contenedor de perfil de empleado -->
    <div class="row">
        <!-- Columna izquierda: Foto del empleado -->
        <div class="col-12 col-md-4">
            <div class="card p-3 mb-4 text-center">
                <?php if($trabajador->Foto): ?>
                    <img 
                        src="<?php echo e(url('storage/' . $trabajador->Foto)); ?>" 
                        class="img-fluid mb-3 profile-picture rounded-circle" 
                        alt="Foto de <?php echo e($trabajador->Nombre); ?>">
                <?php else: ?>
                    <img 
                        src="<?php echo e(url('images/default-avatar.png')); ?>" 
                        class="img-fluid mb-3 rounded-circle" 
                        alt="Imagen predeterminada">
                <?php endif; ?>

                <a href="<?php echo e(route('perfiles.editar', $trabajador->id)); ?>" 
                   class="btn btn-outline-primary btn-custom-width mb-2">
                   Actualizar mi perfil
                </a>

                <a href="<?php echo e(route('perfiles.cambiar_contraseña', $trabajador->id)); ?>" 
                   class="btn btn-outline-primary btn-custom-width mb-3">
                   Cambiar mi contraseña
                </a>

                <a href="<?php echo e(route('perfiles.solicitudes')); ?>" 
                   class="btn btn-primary btn-custom-width mb-3">
                   Consultar mis solicitudes
                </a>

                <a href="<?php echo e(route('solicitudes.create')); ?>" 
                   class="btn btn-primary btn-custom-width mb-3">
                   Solicitar cambio de datos
                </a>

                <a href="<?php echo e(route('vacaciones.create')); ?>" 
                   class="btn btn-primary btn-custom-width">
                    Solicitar Permiso de Días
                </a>
            </div>
        </div>

        <!-- Columna derecha: Información del empleado -->
        <div class="col-12 col-md-8">
            <div class="card p-4 mb-4">

                <!-- Acordeón para Información Personal -->
                <div class="accordion" id="accordionPerfil">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPersonal">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapsePersonal" aria-expanded="true" aria-controls="collapsePersonal">
                                <i class="fa-solid fa-user me-2"></i> Información Personal
                            </button>
                        </h2>
                        <div id="collapsePersonal" class="accordion-collapse collapse show"
                             aria-labelledby="headingPersonal" data-bs-parent="#accordionPerfil">
                            <div class="accordion-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th><i class="fa-solid fa-id-card me-2"></i>RUT</th>
                                            <td><?php echo e($trabajador->Rut); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user me-2"></i>Nombre Completo</th>
                                            <td>
                                                <?php echo e($trabajador->Nombre); ?> <?php echo e($trabajador->SegundoNombre); ?>

                                                <?php echo e($trabajador->ApellidoPaterno); ?> <?php echo e($trabajador->ApellidoMaterno); ?>

                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-days me-2"></i>Fecha de Nacimiento</th>
                                            <td><?php echo e($trabajador->FechaNacimiento->translatedFormat('d F, Y')); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-cake-candles me-2"></i>Edad</th>
                                            <td><?php echo e($trabajador->edad); ?> años</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-phone me-2"></i>Número Celular</th>
                                            <td><?php echo e($trabajador->numero_celular); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user-group me-2"></i>Contacto de Emergencia</th>
                                            <td><?php echo e($trabajador->nombre_emergencia); ?> |
                                                Número: <?php echo e($trabajador->contacto_emergencia); ?>

                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-location-dot me-2"></i>Dirección</th>
                                            <td><?php echo e($trabajador->calle); ?> | <?php echo e($trabajador->comuna->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-envelope me-2"></i>Correo Personal</th>
                                            <td><?php echo e($trabajador->CorreoPersonal); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-university me-2"></i>Banco</th>
                                            <td><?php echo e($trabajador->banco); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-credit-card me-2"></i>Tipo de Cuenta</th>
                                            <td><?php echo e($trabajador->tipo_cuenta); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Acordeón para Información de Empleo -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingEmpleo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseEmpleo" aria-expanded="false" aria-controls="collapseEmpleo">
                                <i class="fa-solid fa-briefcase me-2"></i> Información de Empleo
                            </button>
                        </h2>
                        <div id="collapseEmpleo" class="accordion-collapse collapse"
                             aria-labelledby="headingEmpleo" data-bs-parent="#accordionPerfil">
                            <div class="accordion-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th><i class="fa-solid fa-building me-2"></i>Empresa</th>
                                            <td><?php echo e($trabajador->empresa->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-envelope me-2"></i>Correo Corporativo</th>
                                            <td><?php echo e($trabajador->user->email); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-check me-2"></i>Fecha de Contratación</th>
                                            <td><?php echo e($trabajador->fecha_inicio_trabajo->translatedFormat('d F, Y')); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user-tie me-2"></i>Jefe Área</th>
                                            <td><?php echo e($trabajador->jefe->nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-user-tie me-2"></i>Cargo</th>
                                            <td><?php echo e($trabajador->cargo->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-piggy-bank me-2"></i>AFP</th>
                                            <td><?php echo e($trabajador->afp->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-heart-pulse me-2"></i>Salud</th>
                                            <td><?php echo e($trabajador->salud->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-ring me-2"></i>Estado Civil</th>
                                            <td><?php echo e($trabajador->estadoCivil->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-check me-2"></i>Contrato Firmado</th>
                                            <td><?php echo e($trabajador->ContratoFirmado); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-check me-2"></i>Fecha Inicio Contrato</th>
                                            <td><?php echo e($trabajador->fecha_inicio_contrato->translatedFormat('d F, Y')); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-file-signature me-2"></i>Anexo Contrato</th>
                                            <td><?php echo e($trabajador->AnexoContrato); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-clipboard-list me-2"></i>Situación</th>
                                            <td><?php echo e($trabajador->situacion->Nombre); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-clock me-2"></i>Turno</th>
                                            <td><?php echo e($trabajador->turno ? $trabajador->turno->nombre : 'No asignado'); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-utensils me-2"></i>Casino</th>
                                            <td><?php echo e($trabajador->Casino); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-child me-2"></i>Hijos</th>
                                            <td><?php echo e($trabajador->hijos->count()); ?> hijo(s)</td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa-solid fa-calendar-days me-2"></i>Sistema de Trabajo</th>
                                            <td><?php echo e($trabajador->sistemaTrabajo ? $trabajador->sistemaTrabajo->nombre : 'No asignado'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- Fin accordion-item -->
                </div> <!-- Fin accordion -->

            </div> <!-- Fin card p-4 -->
        </div> <!-- Fin col-md-8 -->
    </div> <!-- Fin row -->
</div> <!-- Fin container -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/perfiles/perfil.blade.php ENDPATH**/ ?>