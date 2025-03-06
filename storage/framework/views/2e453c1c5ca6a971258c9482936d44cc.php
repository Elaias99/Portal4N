
<?php
    // Determinar si se trata de creación o edición (si no existe $empleado, es creación)
    $esCreacion = !isset($empleado);

    // Arreglo de campos obligatorios según las reglas de validación de cada Request
    $camposObligatorios = $esCreacion 
        ? [
            'Rut', 'Nombre', 'ApellidoPaterno', 'ApellidoMaterno',
            'FechaNacimiento', 'CorreoPersonal', 'Casino', 'ContratoFirmado',
            'AnexoContrato', 'empresa_id', 'cargo_id', 'situacion_id',
            'estado_civil_id', 'comuna_id', 'afp_id', 'salud_id',
            'sistema_trabajo_id', 'turno_id', 'salario_bruto', 'calle',
            'numero_celular', 'nombre_emergencia', 'contacto_emergencia',
            'fecha_inicio_trabajo', 'fecha_inicio_contrato',
            'banco', 'numero_cuenta', 'tipo_cuenta', 'Rut_Empresa', 'id_jefe'
        ]
        : [
            'Rut', 'Nombre', 'ApellidoPaterno', 'ApellidoMaterno',
            'FechaNacimiento', 'Casino', 'ContratoFirmado', 'AnexoContrato',
            'empresa_id', 'cargo_id', 'situacion_id',
            'estado_civil_id', 'comuna_id', 'afp_id', 'salud_id',
            'sistema_trabajo_id', 'turno_id', 'salario_bruto', 'calle',
            'numero_celular', 'fecha_inicio_trabajo', 'fecha_inicio_contrato',
            'id_jefe'
        ];

    // Función para mostrar el asterisco si el campo es obligatorio
    function mostrarAsterisco($campo, $camposObligatorios) {
        return in_array($campo, $camposObligatorios) ? '<span class="text-danger">*</span>' : '';
    }
?>

<?php if($errors->any()): ?>
    <div class="alert alert-danger">
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<div class="alert alert-info text-center mb-3" role="alert">
    <strong>Atención:</strong> Los campos marcados con <span class="text-danger">*</span> son obligatorios.
</div>



<?php echo $__env->make('partials.tallas_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('partials.comunas_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('partials.hijos_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="container mt-4">
    <form method="POST" action="<?php echo e($esCreacion ? route('empleados.store') : route('empleados.update', $empleado->id)); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php if(!$esCreacion): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?>

        <div class="accordion" id="accordionExample">
            <!-- Sección 1: Información Personal -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        Información Personal
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="Rut">Rut <?php echo mostrarAsterisco('Rut', $camposObligatorios); ?></label>
                                <input type="text" name="Rut" id="Rut" class="form-control" value="<?php echo e(isset($empleado->Rut) ? $empleado->Rut : old('Rut')); ?>">
                                <?php if($errors->has('Rut')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('Rut')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="Nombre">Nombre <?php echo mostrarAsterisco('Nombre', $camposObligatorios); ?></label>
                                <input type="text" name="Nombre" id="Nombre" class="form-control" value="<?php echo e(isset($empleado->Nombre) ? $empleado->Nombre : old('Nombre')); ?>">
                                <?php if($errors->has('Nombre')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('Nombre')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="SegundoNombre">Segundo Nombre</label>
                                <input type="text" name="SegundoNombre" id="SegundoNombre" class="form-control" value="<?php echo e(isset($empleado->SegundoNombre) ? $empleado->SegundoNombre : old('SegundoNombre')); ?>">
                                <?php if($errors->has('SegundoNombre')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('SegundoNombre')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="TercerNombre">Tercer Nombre</label>
                                <input type="text" name="TercerNombre" id="TercerNombre" class="form-control" value="<?php echo e(isset($empleado->TercerNombre) ? $empleado->TercerNombre : old('TercerNombre')); ?>">
                                <?php if($errors->has('TercerNombre')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('TercerNombre')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ApellidoPaterno">Apellido Paterno <?php echo mostrarAsterisco('ApellidoPaterno', $camposObligatorios); ?></label>
                                <input type="text" name="ApellidoPaterno" id="ApellidoPaterno" class="form-control" value="<?php echo e(isset($empleado->ApellidoPaterno) ? $empleado->ApellidoPaterno : old('ApellidoPaterno')); ?>">
                                <?php if($errors->has('ApellidoPaterno')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('ApellidoPaterno')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ApellidoMaterno">Apellido Materno <?php echo mostrarAsterisco('ApellidoMaterno', $camposObligatorios); ?></label>
                                <input type="text" name="ApellidoMaterno" id="ApellidoMaterno" class="form-control" value="<?php echo e(isset($empleado->ApellidoMaterno) ? $empleado->ApellidoMaterno : old('ApellidoMaterno')); ?>">
                                <?php if($errors->has('ApellidoMaterno')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('ApellidoMaterno')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="FechaNacimiento">Fecha de Nacimiento <?php echo mostrarAsterisco('FechaNacimiento', $camposObligatorios); ?></label>
                                <input type="date" name="FechaNacimiento" id="FechaNacimiento" class="form-control" value="<?php echo e(isset($empleado->FechaNacimiento) ? $empleado->FechaNacimiento->format('Y-m-d') : old('FechaNacimiento')); ?>">
                                <?php if($errors->has('FechaNacimiento')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('FechaNacimiento')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Datos Bancarios -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingBankDetails">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBankDetails" aria-expanded="false" aria-controls="collapseBankDetails">
                        Datos Bancarios
                    </button>
                </h2>
                <div id="collapseBankDetails" class="accordion-collapse collapse" aria-labelledby="headingBankDetails" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="banco">Banco <?php echo mostrarAsterisco('banco', $camposObligatorios); ?></label>
                                <select name="banco" id="banco" class="form-control">
                                    <option value="">Seleccione un banco</option>
                                    <option value="NOREGISTRO" <?php echo e(old('banco', $empleado->banco ?? '') == 'NOREGISTRO' ? 'selected' : ''); ?>>SIN REGISTRO</option>
                                    <option value="BANCO ESTADO" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO ESTADO' ? 'selected' : ''); ?>>BANCO ESTADO</option>
                                    <option value="BANCO CHILE" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO CHILE' ? 'selected' : ''); ?>>BANCO CHILE</option>
                                    <option value="BANCO FALABELLA" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO FALABELLA' ? 'selected' : ''); ?>>BANCO FALABELLA</option>
                                    <option value="BANCO SANTANDER" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO SANTANDER' ? 'selected' : ''); ?>>BANCO SANTANDER</option>
                                    <option value="BANCO BCI" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO BCI' ? 'selected' : ''); ?>>BANCO BCI</option>
                                    <option value="BANCO BICE" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO BICE' ? 'selected' : ''); ?>>BANCO BICE</option>
                                    <option value="BANCO CONSORCIO" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO CONSORCIO' ? 'selected' : ''); ?>>BANCO CONSORCIO</option>
                                    <option value="BANCO SCOTIABANK" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO SCOTIABANK' ? 'selected' : ''); ?>>BANCO SCOTIABANK</option>
                                    <option value="BANCO SECURITY" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO SECURITY' ? 'selected' : ''); ?>>BANCO SECURITY</option>
                                    <option value="BANCO CORPBANCA" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO CORPBANCA' ? 'selected' : ''); ?>>BANCO CORPBANCA</option>
                                    <option value="BANCO RIPLEY" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO RIPLEY' ? 'selected' : ''); ?>>BANCO RIPLEY</option>
                                    <option value="BANCO ITAU" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO ITAU' ? 'selected' : ''); ?>>BANCO ITAU</option>
                                    <option value="BANCO PARIS" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO PARIS' ? 'selected' : ''); ?>>BANCO PARIS</option>
                                    <option value="BANCO DEL DESARROLLO" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO DEL DESARROLLO' ? 'selected' : ''); ?>>BANCO DEL DESARROLLO</option>
                                    <option value="BANCO COPEUCH" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO COPEUCH' ? 'selected' : ''); ?>>BANCO COPEUCH</option>
                                    <option value="BANCO BBVA" <?php echo e(old('banco', $empleado->banco ?? '') == 'BANCO BBVA' ? 'selected' : ''); ?>>BANCO BBVA</option>
                                    <option value="WEBPAY PAGO ONLINE" <?php echo e(old('banco', $empleado->banco ?? '') == 'WEBPAY PAGO ONLINE' ? 'selected' : ''); ?>>WEBPAY PAGO ONLINE</option>
                                    <option value="MERCADO PAGO" <?php echo e(old('banco', $empleado->banco ?? '') == 'MERCADO PAGO' ? 'selected' : ''); ?>>MERCADO PAGO</option>
                                    <option value="TENPO" <?php echo e(old('banco', $empleado->banco ?? '') == 'TENPO' ? 'selected' : ''); ?>>TENPO</option>
                                    
                                </select>
                                <?php if($errors->has('banco')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('banco')); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="numero_cuenta">Número de Cuenta <?php echo mostrarAsterisco('numero_cuenta', $camposObligatorios); ?></label>
                                <div class="input-group">
                                    <input type="text" name="numero_cuenta" id="numero_cuenta" class="form-control" 
                                           value="<?php echo e(isset($empleado->numero_cuenta) ? $empleado->numero_cuenta : old('numero_cuenta')); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('numero_cuenta').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                <?php if($errors->has('numero_cuenta')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('numero_cuenta')); ?></span>
                                <?php endif; ?>
                            </div>
                            

                            <div class="col-md-6 form-group">
                                <label for="tipo_cuenta">Tipo de Cuenta <?php echo mostrarAsterisco('tipo_cuenta', $camposObligatorios); ?></label>
                                <select name="tipo_cuenta" id="tipo_cuenta" class="form-control">
                                    <option value="">Seleccione el tipo de cuenta</option>
                                    <option value="No Registro" <?php echo e(old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'No Registro' ? 'selected' : ''); ?>>Sin Registro</option>
                                    <option value="Cuenta Corriente" <?php echo e(old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta Corriente' ? 'selected' : ''); ?>>Cuenta Corriente</option>
                                    <option value="Cuenta Vista" <?php echo e(old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta Vista' ? 'selected' : ''); ?>>Cuenta Vista</option>
                                    <option value="Cuenta de Ahorro" <?php echo e(old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta de Ahorro' ? 'selected' : ''); ?>>Cuenta de Ahorro</option>
                                    <option value="Cuenta Rut" <?php echo e(old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta Rut' ? 'selected' : ''); ?>>Cuenta Rut</option>
                                </select>
                                <?php if($errors->has('tipo_cuenta')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('tipo_cuenta')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Información de Contacto -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        Información de Contacto
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div class="row">


                            <div class="col-md-6 form-group">
                                <label for="CorreoPersonal">Correo Personal <?php echo mostrarAsterisco('CorreoPersonal', $camposObligatorios); ?></label>
                                <div class="input-group">
                                    <input type="email" name="CorreoPersonal" id="CorreoPersonal" class="form-control" 
                                           value="<?php echo e(isset($empleado->CorreoPersonal) ? $empleado->CorreoPersonal : old('CorreoPersonal')); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('CorreoPersonal').value='no@registro.cl'">
                                        No hay registro
                                    </button>
                                </div>
                                <?php if($errors->has('CorreoPersonal')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('CorreoPersonal')); ?></span>
                                <?php endif; ?>
                            </div>
                            


                            <div class="col-md-6 form-group">
                                <label for="calle">Domicilio <?php echo mostrarAsterisco('calle', $camposObligatorios); ?></label>
                                <div class="input-group">
                                    <input type="text" name="calle" id="calle" class="form-control" 
                                           value="<?php echo e(isset($empleado->calle) ? $empleado->calle : old('calle')); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('calle').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                <?php if($errors->has('calle')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('calle')); ?></span>
                                <?php endif; ?>
                            </div>
                            


                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="comuna_id">Comuna <?php echo mostrarAsterisco('comuna_id', $camposObligatorios); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="comuna_nombre" placeholder="<?php echo e(isset($empleado->comuna) ? $empleado->comuna->Nombre . ', ' . $empleado->comuna->region->Nombre : 'Haz clic en el botón para seleccionar una comuna'); ?>" value="<?php echo e(isset($empleado->comuna) ? $empleado->comuna->Nombre . ', ' . $empleado->comuna->region->Nombre : ''); ?>" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#comunasModal">
                                                <i class="fas fa-map-marker-alt"></i> Seleccionar
                                            </button>
                                        </div>
                                    </div>
                                    <?php if($errors->has('comuna_id')): ?>
                                        <span class="text-danger"><?php echo e($errors->first('comuna_id')); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>



                            <div class="col-md-6 form-group">
                                <label for="numero_celular">Número de celular <?php echo mostrarAsterisco('numero_celular', $camposObligatorios); ?></label>
                                <div class="input-group">
                                    <input type="text" name="numero_celular" id="numero_celular" class="form-control" 
                                           value="<?php echo e(isset($empleado->numero_celular) ? $empleado->numero_celular : old('numero_celular')); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('numero_celular').value='0'">
                                        No hay registro
                                    </button>
                                </div>
                                <?php if($errors->has('numero_celular')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('numero_celular')); ?></span>
                                <?php endif; ?>
                            </div>
                            



                            <div class="col-md-6 form-group">
                                <!-- Número Emergencia -->
                                <label for="contacto_emergencia">Número Emergencia <?php echo mostrarAsterisco('contacto_emergencia', $camposObligatorios); ?></label>
                                <div class="input-group">
                                    <input type="text" name="contacto_emergencia" id="contacto_emergencia" class="form-control" 
                                           value="<?php echo e(isset($empleado->contacto_emergencia) ? $empleado->contacto_emergencia : old('contacto_emergencia')); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('contacto_emergencia').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                <?php if($errors->has('contacto_emergencia')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('contacto_emergencia')); ?></span>
                                <?php endif; ?>
                            
                                <!-- Persona a llamar -->
                                <label for="nombre_emergencia">Persona a llamar <?php echo mostrarAsterisco('nombre_emergencia', $camposObligatorios); ?></label>
                                <div class="input-group">
                                    <input type="text" name="nombre_emergencia" id="nombre_emergencia" class="form-control" 
                                           value="<?php echo e(isset($empleado->nombre_emergencia) ? $empleado->nombre_emergencia : old('nombre_emergencia')); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('nombre_emergencia').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                <?php if($errors->has('nombre_emergencia')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('nombre_emergencia')); ?></span>
                                <?php endif; ?>
                            </div>
                            



                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 3: Información Laboral -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        Información Laboral
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="Foto">Foto</label>
                                <?php if(isset($empleado->Foto)): ?>
                                    <img src="<?php echo e(asset('storage').'/'.$empleado->Foto); ?>" alt="Foto" width="100">
                                <?php endif; ?>
                                <input type="file" name="Foto" id="Foto" class="form-control">
                                <?php if($errors->has('Foto')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('Foto')); ?></span>
                                    <p class="text-warning">Por favor, vuelve a subir la imagen si se produjo un error.</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="salario_bruto">Salario Bruto <?php echo mostrarAsterisco('salario_bruto', $camposObligatorios); ?></label>
                                <input type="number" name="salario_bruto" id="salario_bruto" class="form-control" step="0.01" min="0" value="<?php echo e(isset($empleado->salario_bruto) ? $empleado->salario_bruto : old('salario_bruto')); ?>">
                                <?php if($errors->has('salario_bruto')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('salario_bruto')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="fecha_inicio_trabajo">Fecha de Inicio de Trabajo <?php echo mostrarAsterisco('fecha_inicio_trabajo', $camposObligatorios); ?></label>
                                <input type="date" name="fecha_inicio_trabajo" id="fecha_inicio_trabajo" class="form-control" value="<?php echo e(isset($empleado->fecha_inicio_trabajo) ? $empleado->fecha_inicio_trabajo->format('Y-m-d') : old('fecha_inicio_trabajo')); ?>">
                                <?php if($errors->has('fecha_inicio_trabajo')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('fecha_inicio_trabajo')); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'sistema_trabajo_id','label' => 'Sistema de Trabajo <span class=\'text-danger\'>*</span>','options' => $sistemasTrabajo,'selected' => $empleado->sistema_trabajo_id ?? null] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
                            
                            <div class="col-md-6 form-group">
                                <label for="Casino">Casino <?php echo mostrarAsterisco('Casino', $camposObligatorios); ?></label>
                                <select name="Casino" id="Casino" class="form-control">
                                    <option value="Sí" <?php echo e(old('Casino', $empleado->Casino ?? '') == 'Sí' ? 'selected' : ''); ?>>Sí</option>
                                    <option value="No" <?php echo e(old('Casino', $empleado->Casino ?? '') == 'No' ? 'selected' : ''); ?>>No</option>
                                </select>
                                <?php if($errors->has('Casino')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('Casino')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ContratoFirmado">Contrato Firmado <?php echo mostrarAsterisco('ContratoFirmado', $camposObligatorios); ?></label>
                                <select name="ContratoFirmado" id="ContratoFirmado" class="form-control">
                                    <option value="Sí" <?php echo e((old('ContratoFirmado') == 'Sí' || (isset($empleado->ContratoFirmado) && $empleado->ContratoFirmado == 'Sí')) ? 'selected' : ''); ?>>Sí</option>
                                    <option value="No" <?php echo e((old('ContratoFirmado') == 'No' || (isset($empleado->ContratoFirmado) && $empleado->ContratoFirmado == 'No')) ? 'selected' : ''); ?>>No</option>
                                </select>
                                <?php if($errors->has('ContratoFirmado')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('ContratoFirmado')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="fecha_inicio_contrato">Fecha inicio contrato <?php echo mostrarAsterisco('fecha_inicio_contrato', $camposObligatorios); ?></label>
                                <input type="date" name="fecha_inicio_contrato" id="fecha_inicio_contrato" class="form-control" value="<?php echo e(isset($empleado->fecha_inicio_contrato) ? $empleado->fecha_inicio_contrato->format('Y-m-d') : old('fecha_inicio_contrato')); ?>">
                                <?php if($errors->has('fecha_inicio_contrato')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('fecha_inicio_contrato')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="AnexoContrato">Anexo Contrato <?php echo mostrarAsterisco('AnexoContrato', $camposObligatorios); ?></label>
                                <select name="AnexoContrato" id="AnexoContrato" class="form-control">
                                    <option value="Sí" <?php echo e((old('AnexoContrato') == 'Sí' || (isset($empleado->AnexoContrato) && $empleado->AnexoContrato == 'Sí')) ? 'selected' : ''); ?>>Sí</option>
                                    <option value="No" <?php echo e((old('AnexoContrato') == 'No' || (isset($empleado->AnexoContrato) && $empleado->AnexoContrato == 'No')) ? 'selected' : ''); ?>>No</option>
                                </select>
                                <?php if($errors->has('AnexoContrato')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('AnexoContrato')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="id_jefe">Jefe de Área <?php echo mostrarAsterisco('id_jefe', $camposObligatorios); ?></label>
                                <select name="id_jefe" id="id_jefe" class="form-control">
                                    <option value="">Seleccione un jefe</option>
                                    <?php $__currentLoopData = $jefes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $jefe): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($jefe->id); ?>" <?php echo e(old('id_jefe', $empleado->id_jefe ?? '') == $jefe->id ? 'selected' : ''); ?>>
                                            <?php echo e($jefe->nombre); ?> - <?php echo e($jefe->area); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php if($errors->has('id_jefe')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('id_jefe')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 4: Otros Detalles -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        Otros Detalles
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="empresa_id">Empresa <?php echo mostrarAsterisco('empresa_id', $camposObligatorios); ?></label>
                                <select name="empresa_id" id="empresa_id" class="form-control">
                                    <option value="">Seleccione una empresa</option>
                                    <option value="7" data-rut="77.346.078-7" <?php echo e(old('empresa_id', $empleado->empresa_id ?? '') == '7' ? 'selected' : ''); ?>>4NORTES LOGISTICA SPA</option>
                                    <option value="8" data-rut="77.639.015-1" <?php echo e(old('empresa_id', $empleado->empresa_id ?? '') == '8' ? 'selected' : ''); ?>>TRANSPORTES Y DISTRIBUCION PMCB</option>
                                </select>
                                <?php if($errors->has('empresa_id')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('empresa_id')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="Rut_Empresa">Rut de la empresa <?php echo mostrarAsterisco('Rut_Empresa', $camposObligatorios); ?></label>
                                <input type="text" name="Rut_Empresa" id="Rut_Empresa" class="form-control" value="<?php echo e(isset($empleado->Rut_Empresa) ? $empleado->Rut_Empresa : old('Rut_Empresa')); ?>" readonly>
                                <?php if($errors->has('Rut_Empresa')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('Rut_Empresa')); ?></span>
                                <?php endif; ?>
                            </div>


                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'cargo_id','label' => 'Cargo <span class=\'text-danger\'>*</span>','options' => $cargos,'selected' => $empleado->cargo_id ?? null] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>



                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'situacion_id','label' => 'Situación <span class=\'text-danger\'>*</span>','options' => $situacions,'selected' => $empleado->situacion_id ?? null] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>


                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'estado_civil_id','label' => 'Estado Civil <span class=\'text-danger\'>*</span>','options' => $estadoCivils,'selected' => old('estado_civil_id', $selected ?? '')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>




                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'afp_id','label' => 'AFP <span class=\'text-danger\'>*</span>','options' => $afps,'selected' => $empleado->afp_id ?? null] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>



                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'salud_id','label' => 'Salud <span class=\'text-danger\'>*</span>','options' => $saluds,'selected' => $empleado->salud_id ?? null] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>


                            <?php if (isset($component)) { $__componentOriginal6abf56fdef591b8688e079511b00ac3f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6abf56fdef591b8688e079511b00ac3f = $attributes; } ?>
<?php $component = App\View\Components\SelectOtro::resolve(['name' => 'turno_id','label' => 'Turno <span class=\'text-danger\'>*</span>','options' => $turnos,'selected' => $empleado->turno_id ?? null] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('select-otro'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SelectOtro::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $attributes = $__attributesOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__attributesOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6abf56fdef591b8688e079511b00ac3f)): ?>
<?php $component = $__componentOriginal6abf56fdef591b8688e079511b00ac3f; ?>
<?php unset($__componentOriginal6abf56fdef591b8688e079511b00ac3f); ?>
<?php endif; ?>

                            <div class="form-group">
                                <label for="hijos">Hijos</label>
                                <br>
                                <button type="button" class="btn btn-outline-primary btn-block" data-toggle="modal" data-target="#hijosModal">
                                    Gestionar Hijos
                                </button>
                                <?php if($errors->has('hijos')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('hijos')); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="tallas">Tallas</label>
                                <br>
                                <button type="button" class="btn btn-outline-primary btn-block" data-toggle="modal" data-target="#tallasModal">
                                    Gestionar Tallas
                                </button>
                                <?php if($errors->has('tallas')): ?>
                                    <span class="text-danger"><?php echo e($errors->first('tallas')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="<?php echo e(url('/empleados')); ?>" class="btn btn-primary mt-3">
            <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
        </a>
        <div class="form-group text-center mt-4">
            <button type="submit" class="btn btn-primary"><?php echo e($esCreacion ? 'Crear' : 'Actualizar'); ?> Empleado</button>
        </div>
    </form>
</div>

<!-- Script para seleccionar comuna desde el modal -->
<script>
    function seleccionarComuna() {
        const comunaSeleccionada = document.querySelector('input[name="comuna_id"]:checked');
        if (comunaSeleccionada) {
            const comunaNombre = comunaSeleccionada.nextElementSibling.textContent.trim();
            const regionNombre = comunaSeleccionada.closest('.accordion-item').querySelector('.accordion-button').textContent.trim();
            document.getElementById('comuna_nombre').value = comunaNombre + ', ' + regionNombre;
        } else {
            alert('Por favor selecciona una comuna');
        }
    }
</script>

<!-- Script para rellenar automáticamente el campo Rut_Empresa -->
<script>
    document.getElementById('empresa_id').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var rutEmpresa = selectedOption.getAttribute('data-rut');
        document.getElementById('Rut_Empresa').value = rutEmpresa || '';
    });
</script>

<!-- Script para mostrar campo adicional en Cargo si se selecciona "otro" -->
<script>
    function mostrarCampoCargo() {
        var selectCargo = document.getElementById('cargo_id');
        var nuevoCargo = document.getElementById('nuevoCargo');
        if (selectCargo.value === 'otro') {
            nuevoCargo.style.display = 'block';
        } else {
            nuevoCargo.style.display = 'none';
        }
    }
</script>
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/empleados/form.blade.php ENDPATH**/ ?>