<div>
    <label for="<?php echo e($name); ?>"><?php echo $label; ?></label>

    <select name="<?php echo e($name); ?>" id="<?php echo e($name); ?>" class="form-control" onchange="mostrarCampoOtro('<?php echo e($name); ?>')">
        <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($option->id); ?>" <?php echo e(old($name, $selected ?? '') == $option->id ? 'selected' : ''); ?>>
                <?php echo e($option->Nombre ?? $option->nombre); ?> <!-- Mostrar Nombre o nombre -->
            </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <option value="otro">Otro</option> <!-- Opción para agregar nuevo -->
    </select>

    <!-- Campo para ingresar el nuevo valor -->
    <div id="nuevo_<?php echo e($name == 'salud_id' ? 'salud' : 
        ($name == 'cargo_id' ? 'cargo' : 
        ($name == 'estado_civil_id' ? 'estado_civil' : 
        ($name == 'turno_id' ? 'turno' : 
        ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
        ($name == 'situacion_id' ? 'situacion' : 
        ($name == 'afp_id' ? 'afp' : $name))))))); ?>" style="display: none;">
        <label for="nuevo_<?php echo e($name == 'salud_id' ? 'salud' : 
            ($name == 'cargo_id' ? 'cargo' : 
            ($name == 'estado_civil_id' ? 'estado_civil' : 
            ($name == 'turno_id' ? 'turno' : 
            ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            ($name == 'situacion_id' ? 'situacion' : 
            ($name == 'afp_id' ? 'afp' : $name))))))); ?>">Nuevo <?php echo e($label); ?></label>
        <input type="text" name="nuevo_<?php echo e($name == 'salud_id' ? 'salud' : 
            ($name == 'cargo_id' ? 'cargo' : 
            ($name == 'estado_civil_id' ? 'estado_civil' : 
            ($name == 'turno_id' ? 'turno' : 
            ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            ($name == 'situacion_id' ? 'situacion' : 
            ($name == 'afp_id' ? 'afp' : $name))))))); ?>" id="nuevo_<?php echo e($name == 'salud_id' ? 'salud' : 
            ($name == 'cargo_id' ? 'cargo' : 
            ($name == 'estado_civil_id' ? 'estado_civil' : 
            ($name == 'turno_id' ? 'turno' : 
            ($name == 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            ($name == 'situacion_id' ? 'situacion' : 
            ($name == 'afp_id' ? 'afp' : $name))))))); ?>" class="form-control" placeholder="Ingrese nuevo <?php echo e($label); ?>">

        <!-- Campos adicionales para AFP -->
        <?php if($name == 'afp_id'): ?>
            <div class="form-group">
                <label for="tasa_cotizacion">Tasa de Cotización (%):</label>
                <input type="number" name="tasa_cotizacion" class="form-control" id="tasa_cotizacion" step="0.01" placeholder="Ingrese la tasa de cotización">
            </div>

            <div class="form-group">
                <label for="tasa_sis">Tasa SIS (%):</label>
                <input type="number" name="tasa_sis" class="form-control" id="tasa_sis" step="0.01" placeholder="Ingrese la tasa SIS">
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function mostrarCampoOtro(name) {
        var nuevoCampoId = 'nuevo_' + (
            name === 'salud_id' ? 'salud' : 
            (name === 'cargo_id' ? 'cargo' : 
            (name === 'estado_civil_id' ? 'estado_civil' : 
            (name === 'turno_id' ? 'turno' : 
            (name === 'sistema_trabajo_id' ? 'sistema_trabajo' : 
            (name === 'situacion_id' ? 'situacion' : 
            (name === 'afp_id' ? 'afp' : name))))))
        );
        var select = document.getElementById(name);
        var nuevoCampo = document.getElementById(nuevoCampoId);
        nuevoCampo.style.display = select.value === 'otro' ? 'block' : 'none';
    }
</script>





<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/components/select-otro.blade.php ENDPATH**/ ?>