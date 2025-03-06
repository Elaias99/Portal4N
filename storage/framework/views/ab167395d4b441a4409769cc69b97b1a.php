

<?php $__env->startSection('content'); ?>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Crear Peticiones de Días</h1>

    <!-- Mostrar mensajes de error -->
    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Mostrar mensaje de éxito -->
    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <!-- Mostrar siempre los días proporcionales acumulados -->
    <div class="alert alert-info">
        <strong>Días Proporcionales Acumulados:</strong> <?php echo e($diasProporcionales); ?>

    </div>

    <?php if($solicitudPendiente): ?>
        <!-- Mensaje si ya hay una solicitud pendiente -->
        <div class="alert alert-warning">
            Ya tienes una solicitud de vacaciones pendiente. Debes esperar la respuesta antes de hacer otra.
        </div>
    <?php else: ?>
        <!-- Formulario de Solicitud de Vacaciones -->
        <div class="card shadow-sm p-4">
            <form action="<?php echo e(route('vacaciones.store')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="form-group mb-3">
                    <label for="tipo_dia" class="form-label">Tipo de Solicitud de Día</label>
                    <select name="tipo_dia" class="form-control" required>
                        <option value="vacaciones">Días de Vacaciones</option>
                        <option value="administrativo">Días Administrativos</option>
                        <option value="sin_goce_de_sueldo">Permiso sin goce de sueldo</option>
                        <option value="Permiso_fuerza_mayor">Permiso fuerza mayor</option>
                        <option value="licencia_medica">Licencia Médica</option>
                    </select>
                    <small class="form-text text-muted">
                        * Nota: Solo los <strong>Días de Vacaciones</strong> afectan el saldo de días proporcionales acumulados. 
                        Los demás tipos de permisos no afectan tu saldo.
                    </small>
                </div>
                
                <div class="form-group mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" required>
                </div>

                <div class="form-group mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" required>
                </div>

                <!-- Nueva sección para adjuntar archivo -->
                <div class="form-group">
                    <label for="archivo">Adjuntar archivo (opcional):</label>
                    <input type="file" name="archivo" class="form-control" id="archivo">
                </div>

                <div class="form-group mb-3">
                    <label for="dias" class="form-label">Número de Días</label>
                    <input type="number" class="form-control" name="dias" id="dias" min="1" required>
                    <small class="form-text text-muted">
                        Ingresa manualmente el número de días de la solicitud. Verifica que no incluyas días feriados o fines de semana si aplican.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Enviar Solicitud</button>
            </form>
        </div>
    <?php endif; ?>

    <a href="<?php echo e(route('empleados.perfil')); ?>" class="btn btn-outline-secondary btn-block mt-4">Atrás</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/vacaciones/create.blade.php ENDPATH**/ ?>