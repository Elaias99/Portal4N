{{-- resources/views/empleados/form.blade.php --}}
@php
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
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="alert alert-info text-center mb-3" role="alert">
    <strong>Atención:</strong> Los campos marcados con <span class="text-danger">*</span> son obligatorios.
</div>



@include('partials.tallas_modal')
@include('partials.comunas_modal')
@include('partials.hijos_modal')

<div class="container mt-4">
    <form method="POST" action="{{ $esCreacion ? route('empleados.store') : route('empleados.update', $empleado->id) }}" enctype="multipart/form-data">
        @csrf
        @if(!$esCreacion)
            @method('PUT')
        @endif

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
                                <label for="Rut">Rut {!! mostrarAsterisco('Rut', $camposObligatorios) !!}</label>
                                <input type="text" name="Rut" id="Rut" class="form-control" value="{{ isset($empleado->Rut) ? $empleado->Rut : old('Rut') }}">
                                @if ($errors->has('Rut'))
                                    <span class="text-danger">{{ $errors->first('Rut') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="Nombre">Nombre {!! mostrarAsterisco('Nombre', $camposObligatorios) !!}</label>
                                <input type="text" name="Nombre" id="Nombre" class="form-control" value="{{ isset($empleado->Nombre) ? $empleado->Nombre : old('Nombre') }}">
                                @if ($errors->has('Nombre'))
                                    <span class="text-danger">{{ $errors->first('Nombre') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="SegundoNombre">Segundo Nombre</label>
                                <input type="text" name="SegundoNombre" id="SegundoNombre" class="form-control" value="{{ isset($empleado->SegundoNombre) ? $empleado->SegundoNombre : old('SegundoNombre') }}">
                                @if ($errors->has('SegundoNombre'))
                                    <span class="text-danger">{{ $errors->first('SegundoNombre') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="TercerNombre">Tercer Nombre</label>
                                <input type="text" name="TercerNombre" id="TercerNombre" class="form-control" value="{{ isset($empleado->TercerNombre) ? $empleado->TercerNombre : old('TercerNombre') }}">
                                @if ($errors->has('TercerNombre'))
                                    <span class="text-danger">{{ $errors->first('TercerNombre') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ApellidoPaterno">Apellido Paterno {!! mostrarAsterisco('ApellidoPaterno', $camposObligatorios) !!}</label>
                                <input type="text" name="ApellidoPaterno" id="ApellidoPaterno" class="form-control" value="{{ isset($empleado->ApellidoPaterno) ? $empleado->ApellidoPaterno : old('ApellidoPaterno') }}">
                                @if ($errors->has('ApellidoPaterno'))
                                    <span class="text-danger">{{ $errors->first('ApellidoPaterno') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ApellidoMaterno">Apellido Materno {!! mostrarAsterisco('ApellidoMaterno', $camposObligatorios) !!}</label>
                                <input type="text" name="ApellidoMaterno" id="ApellidoMaterno" class="form-control" value="{{ isset($empleado->ApellidoMaterno) ? $empleado->ApellidoMaterno : old('ApellidoMaterno') }}">
                                @if ($errors->has('ApellidoMaterno'))
                                    <span class="text-danger">{{ $errors->first('ApellidoMaterno') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="FechaNacimiento">Fecha de Nacimiento {!! mostrarAsterisco('FechaNacimiento', $camposObligatorios) !!}</label>
                                <input type="date" name="FechaNacimiento" id="FechaNacimiento" class="form-control" value="{{ isset($empleado->FechaNacimiento) ? $empleado->FechaNacimiento->format('Y-m-d') : old('FechaNacimiento') }}">
                                @if ($errors->has('FechaNacimiento'))
                                    <span class="text-danger">{{ $errors->first('FechaNacimiento') }}</span>
                                @endif
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
                                <label for="banco">Banco {!! mostrarAsterisco('banco', $camposObligatorios) !!}</label>
                                <select name="banco" id="banco" class="form-control">
                                    <option value="">Seleccione un banco</option>
                                    <option value="NOREGISTRO" {{ old('banco', $empleado->banco ?? '') == 'NOREGISTRO' ? 'selected' : '' }}>SIN REGISTRO</option>
                                    <option value="BANCO ESTADO" {{ old('banco', $empleado->banco ?? '') == 'BANCO ESTADO' ? 'selected' : '' }}>BANCO ESTADO</option>
                                    <option value="BANCO CHILE" {{ old('banco', $empleado->banco ?? '') == 'BANCO CHILE' ? 'selected' : '' }}>BANCO CHILE</option>
                                    <option value="BANCO FALABELLA" {{ old('banco', $empleado->banco ?? '') == 'BANCO FALABELLA' ? 'selected' : '' }}>BANCO FALABELLA</option>
                                    <option value="BANCO SANTANDER" {{ old('banco', $empleado->banco ?? '') == 'BANCO SANTANDER' ? 'selected' : '' }}>BANCO SANTANDER</option>
                                    <option value="BANCO BCI" {{ old('banco', $empleado->banco ?? '') == 'BANCO BCI' ? 'selected' : '' }}>BANCO BCI</option>
                                    <option value="BANCO BICE" {{ old('banco', $empleado->banco ?? '') == 'BANCO BICE' ? 'selected' : '' }}>BANCO BICE</option>
                                    <option value="BANCO CONSORCIO" {{ old('banco', $empleado->banco ?? '') == 'BANCO CONSORCIO' ? 'selected' : '' }}>BANCO CONSORCIO</option>
                                    <option value="BANCO SCOTIABANK" {{ old('banco', $empleado->banco ?? '') == 'BANCO SCOTIABANK' ? 'selected' : '' }}>BANCO SCOTIABANK</option>
                                    <option value="BANCO SECURITY" {{ old('banco', $empleado->banco ?? '') == 'BANCO SECURITY' ? 'selected' : '' }}>BANCO SECURITY</option>
                                    <option value="BANCO CORPBANCA" {{ old('banco', $empleado->banco ?? '') == 'BANCO CORPBANCA' ? 'selected' : '' }}>BANCO CORPBANCA</option>
                                    <option value="BANCO RIPLEY" {{ old('banco', $empleado->banco ?? '') == 'BANCO RIPLEY' ? 'selected' : '' }}>BANCO RIPLEY</option>
                                    <option value="BANCO ITAU" {{ old('banco', $empleado->banco ?? '') == 'BANCO ITAU' ? 'selected' : '' }}>BANCO ITAU</option>
                                    <option value="BANCO PARIS" {{ old('banco', $empleado->banco ?? '') == 'BANCO PARIS' ? 'selected' : '' }}>BANCO PARIS</option>
                                    <option value="BANCO DEL DESARROLLO" {{ old('banco', $empleado->banco ?? '') == 'BANCO DEL DESARROLLO' ? 'selected' : '' }}>BANCO DEL DESARROLLO</option>
                                    <option value="BANCO COPEUCH" {{ old('banco', $empleado->banco ?? '') == 'BANCO COPEUCH' ? 'selected' : '' }}>BANCO COPEUCH</option>
                                    <option value="BANCO BBVA" {{ old('banco', $empleado->banco ?? '') == 'BANCO BBVA' ? 'selected' : '' }}>BANCO BBVA</option>
                                    <option value="WEBPAY PAGO ONLINE" {{ old('banco', $empleado->banco ?? '') == 'WEBPAY PAGO ONLINE' ? 'selected' : '' }}>WEBPAY PAGO ONLINE</option>
                                    <option value="MERCADO PAGO" {{ old('banco', $empleado->banco ?? '') == 'MERCADO PAGO' ? 'selected' : '' }}>MERCADO PAGO</option>
                                    <option value="TENPO" {{ old('banco', $empleado->banco ?? '') == 'TENPO' ? 'selected' : '' }}>TENPO</option>
                                    
                                </select>
                                @if ($errors->has('banco'))
                                    <span class="text-danger">{{ $errors->first('banco') }}</span>
                                @endif
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="numero_cuenta">Número de Cuenta {!! mostrarAsterisco('numero_cuenta', $camposObligatorios) !!}</label>
                                <div class="input-group">
                                    <input type="text" name="numero_cuenta" id="numero_cuenta" class="form-control" 
                                           value="{{ isset($empleado->numero_cuenta) ? $empleado->numero_cuenta : old('numero_cuenta') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('numero_cuenta').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                @if ($errors->has('numero_cuenta'))
                                    <span class="text-danger">{{ $errors->first('numero_cuenta') }}</span>
                                @endif
                            </div>
                            

                            <div class="col-md-6 form-group">
                                <label for="tipo_cuenta">Tipo de Cuenta {!! mostrarAsterisco('tipo_cuenta', $camposObligatorios) !!}</label>
                                <select name="tipo_cuenta" id="tipo_cuenta" class="form-control">
                                    <option value="">Seleccione el tipo de cuenta</option>
                                    <option value="No Registro" {{ old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'No Registro' ? 'selected' : '' }}>Sin Registro</option>
                                    <option value="Cuenta Corriente" {{ old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta Corriente' ? 'selected' : '' }}>Cuenta Corriente</option>
                                    <option value="Cuenta Vista" {{ old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta Vista' ? 'selected' : '' }}>Cuenta Vista</option>
                                    <option value="Cuenta de Ahorro" {{ old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta de Ahorro' ? 'selected' : '' }}>Cuenta de Ahorro</option>
                                    <option value="Cuenta Rut" {{ old('tipo_cuenta', $empleado->tipo_cuenta ?? '') == 'Cuenta Rut' ? 'selected' : '' }}>Cuenta Rut</option>
                                </select>
                                @if ($errors->has('tipo_cuenta'))
                                    <span class="text-danger">{{ $errors->first('tipo_cuenta') }}</span>
                                @endif
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
                                <label for="CorreoPersonal">Correo Personal {!! mostrarAsterisco('CorreoPersonal', $camposObligatorios) !!}</label>
                                <div class="input-group">
                                    <input type="email" name="CorreoPersonal" id="CorreoPersonal" class="form-control" 
                                           value="{{ isset($empleado->CorreoPersonal) ? $empleado->CorreoPersonal : old('CorreoPersonal') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('CorreoPersonal').value='no@registro.cl'">
                                        No hay registro
                                    </button>
                                </div>
                                @if ($errors->has('CorreoPersonal'))
                                    <span class="text-danger">{{ $errors->first('CorreoPersonal') }}</span>
                                @endif
                            </div>
                            


                            <div class="col-md-6 form-group">
                                <label for="calle">Domicilio {!! mostrarAsterisco('calle', $camposObligatorios) !!}</label>
                                <div class="input-group">
                                    <input type="text" name="calle" id="calle" class="form-control" 
                                           value="{{ isset($empleado->calle) ? $empleado->calle : old('calle') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('calle').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                @if ($errors->has('calle'))
                                    <span class="text-danger">{{ $errors->first('calle') }}</span>
                                @endif
                            </div>
                            


                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="comuna_id">Comuna {!! mostrarAsterisco('comuna_id', $camposObligatorios) !!}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="comuna_nombre" placeholder="{{ isset($empleado->comuna) ? $empleado->comuna->Nombre . ', ' . $empleado->comuna->region->Nombre : 'Haz clic en el botón para seleccionar una comuna' }}" value="{{ isset($empleado->comuna) ? $empleado->comuna->Nombre . ', ' . $empleado->comuna->region->Nombre : '' }}" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#comunasModal">
                                                <i class="fas fa-map-marker-alt"></i> Seleccionar
                                            </button>
                                        </div>
                                    </div>
                                    @if ($errors->has('comuna_id'))
                                        <span class="text-danger">{{ $errors->first('comuna_id') }}</span>
                                    @endif
                                </div>
                            </div>



                            <div class="col-md-6 form-group">
                                <label for="numero_celular">Número de celular {!! mostrarAsterisco('numero_celular', $camposObligatorios) !!}</label>
                                <div class="input-group">
                                    <input type="text" name="numero_celular" id="numero_celular" class="form-control" 
                                           value="{{ isset($empleado->numero_celular) ? $empleado->numero_celular : old('numero_celular') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('numero_celular').value='0'">
                                        No hay registro
                                    </button>
                                </div>
                                @if ($errors->has('numero_celular'))
                                    <span class="text-danger">{{ $errors->first('numero_celular') }}</span>
                                @endif
                            </div>
                            



                            <div class="col-md-6 form-group">
                                <!-- Número Emergencia -->
                                <label for="contacto_emergencia">Número Emergencia {!! mostrarAsterisco('contacto_emergencia', $camposObligatorios) !!}</label>
                                <div class="input-group">
                                    <input type="text" name="contacto_emergencia" id="contacto_emergencia" class="form-control" 
                                           value="{{ isset($empleado->contacto_emergencia) ? $empleado->contacto_emergencia : old('contacto_emergencia') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('contacto_emergencia').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                @if ($errors->has('contacto_emergencia'))
                                    <span class="text-danger">{{ $errors->first('contacto_emergencia') }}</span>
                                @endif
                            
                                <!-- Persona a llamar -->
                                <label for="nombre_emergencia">Persona a llamar {!! mostrarAsterisco('nombre_emergencia', $camposObligatorios) !!}</label>
                                <div class="input-group">
                                    <input type="text" name="nombre_emergencia" id="nombre_emergencia" class="form-control" 
                                           value="{{ isset($empleado->nombre_emergencia) ? $empleado->nombre_emergencia : old('nombre_emergencia') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('nombre_emergencia').value='No hay registro'">
                                        No hay registro
                                    </button>
                                </div>
                                @if ($errors->has('nombre_emergencia'))
                                    <span class="text-danger">{{ $errors->first('nombre_emergencia') }}</span>
                                @endif
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
                                @if(isset($empleado->Foto))
                                    <img src="{{ asset('storage').'/'.$empleado->Foto }}" alt="Foto" width="100">
                                @endif
                                <input type="file" name="Foto" id="Foto" class="form-control">
                                @if ($errors->has('Foto'))
                                    <span class="text-danger">{{ $errors->first('Foto') }}</span>
                                    <p class="text-warning">Por favor, vuelve a subir la imagen si se produjo un error.</p>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="salario_bruto">Salario Bruto {!! mostrarAsterisco('salario_bruto', $camposObligatorios) !!}</label>
                                <input type="number" name="salario_bruto" id="salario_bruto" class="form-control" step="0.01" min="0" value="{{ isset($empleado->salario_bruto) ? $empleado->salario_bruto : old('salario_bruto') }}">
                                @if ($errors->has('salario_bruto'))
                                    <span class="text-danger">{{ $errors->first('salario_bruto') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="fecha_inicio_trabajo">Fecha de Inicio de Trabajo {!! mostrarAsterisco('fecha_inicio_trabajo', $camposObligatorios) !!}</label>
                                <input type="date" name="fecha_inicio_trabajo" id="fecha_inicio_trabajo" class="form-control" value="{{ isset($empleado->fecha_inicio_trabajo) ? $empleado->fecha_inicio_trabajo->format('Y-m-d') : old('fecha_inicio_trabajo') }}">
                                @if ($errors->has('fecha_inicio_trabajo'))
                                    <span class="text-danger">{{ $errors->first('fecha_inicio_trabajo') }}</span>
                                @endif
                            </div>
                            
                            <x-select-otro 
                                name="sistema_trabajo_id" 
                                label="Sistema de Trabajo <span class='text-danger'>*</span>" 
                                :options="$sistemasTrabajo" 
                                :selected="$empleado->sistema_trabajo_id ?? null" 
                            />
                            
                            <div class="col-md-6 form-group">
                                <label for="Casino">Casino {!! mostrarAsterisco('Casino', $camposObligatorios) !!}</label>
                                <select name="Casino" id="Casino" class="form-control">
                                    <option value="Sí" {{ old('Casino', $empleado->Casino ?? '') == 'Sí' ? 'selected' : '' }}>Sí</option>
                                    <option value="No" {{ old('Casino', $empleado->Casino ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                @if ($errors->has('Casino'))
                                    <span class="text-danger">{{ $errors->first('Casino') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ContratoFirmado">Contrato Firmado {!! mostrarAsterisco('ContratoFirmado', $camposObligatorios) !!}</label>
                                <select name="ContratoFirmado" id="ContratoFirmado" class="form-control">
                                    <option value="Sí" {{ (old('ContratoFirmado') == 'Sí' || (isset($empleado->ContratoFirmado) && $empleado->ContratoFirmado == 'Sí')) ? 'selected' : '' }}>Sí</option>
                                    <option value="No" {{ (old('ContratoFirmado') == 'No' || (isset($empleado->ContratoFirmado) && $empleado->ContratoFirmado == 'No')) ? 'selected' : '' }}>No</option>
                                </select>
                                @if ($errors->has('ContratoFirmado'))
                                    <span class="text-danger">{{ $errors->first('ContratoFirmado') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="fecha_inicio_contrato">Fecha inicio contrato {!! mostrarAsterisco('fecha_inicio_contrato', $camposObligatorios) !!}</label>
                                <input type="date" name="fecha_inicio_contrato" id="fecha_inicio_contrato" class="form-control" value="{{ isset($empleado->fecha_inicio_contrato) ? $empleado->fecha_inicio_contrato->format('Y-m-d') : old('fecha_inicio_contrato') }}">
                                @if ($errors->has('fecha_inicio_contrato'))
                                    <span class="text-danger">{{ $errors->first('fecha_inicio_contrato') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="AnexoContrato">Anexo Contrato {!! mostrarAsterisco('AnexoContrato', $camposObligatorios) !!}</label>
                                <select name="AnexoContrato" id="AnexoContrato" class="form-control">
                                    <option value="Sí" {{ (old('AnexoContrato') == 'Sí' || (isset($empleado->AnexoContrato) && $empleado->AnexoContrato == 'Sí')) ? 'selected' : '' }}>Sí</option>
                                    <option value="No" {{ (old('AnexoContrato') == 'No' || (isset($empleado->AnexoContrato) && $empleado->AnexoContrato == 'No')) ? 'selected' : '' }}>No</option>
                                </select>
                                @if ($errors->has('AnexoContrato'))
                                    <span class="text-danger">{{ $errors->first('AnexoContrato') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="id_jefe">Jefe de Área {!! mostrarAsterisco('id_jefe', $camposObligatorios) !!}</label>
                                <select name="id_jefe" id="id_jefe" class="form-control">
                                    <option value="">Seleccione un jefe</option>
                                    @foreach($jefes as $jefe)
                                        <option value="{{ $jefe->id }}" {{ old('id_jefe', $empleado->id_jefe ?? '') == $jefe->id ? 'selected' : '' }}>
                                            {{ $jefe->nombre }} - {{ $jefe->area }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('id_jefe'))
                                    <span class="text-danger">{{ $errors->first('id_jefe') }}</span>
                                @endif
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
                                <label for="empresa_id">Empresa {!! mostrarAsterisco('empresa_id', $camposObligatorios) !!}</label>
                                <select name="empresa_id" id="empresa_id" class="form-control">
                                    <option value="">Seleccione una empresa</option>
                                    <option value="7" data-rut="77.346.078-7" {{ old('empresa_id', $empleado->empresa_id ?? '') == '7' ? 'selected' : '' }}>4NORTES LOGISTICA SPA</option>
                                    <option value="8" data-rut="77.639.015-1" {{ old('empresa_id', $empleado->empresa_id ?? '') == '8' ? 'selected' : '' }}>TRANSPORTES Y DISTRIBUCION PMCB</option>
                                </select>
                                @if ($errors->has('empresa_id'))
                                    <span class="text-danger">{{ $errors->first('empresa_id') }}</span>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="Rut_Empresa">Rut de la empresa {!! mostrarAsterisco('Rut_Empresa', $camposObligatorios) !!}</label>
                                <input type="text" name="Rut_Empresa" id="Rut_Empresa" class="form-control" value="{{ isset($empleado->Rut_Empresa) ? $empleado->Rut_Empresa : old('Rut_Empresa') }}" readonly>
                                @if ($errors->has('Rut_Empresa'))
                                    <span class="text-danger">{{ $errors->first('Rut_Empresa') }}</span>
                                @endif
                            </div>


                            <x-select-otro 
                                name="cargo_id" 
                                label="Cargo <span class='text-danger'>*</span>" 
                                :options="$cargos" 
                                :selected="$empleado->cargo_id ?? null" 
                            />



                            <x-select-otro 
                                name="situacion_id" 
                                label="Situación <span class='text-danger'>*</span>" 
                                :options="$situacions" 
                                :selected="$empleado->situacion_id ?? null" 
                            />


                            <x-select-otro 
                                name="estado_civil_id" 
                                label="Estado Civil <span class='text-danger'>*</span>" 
                                :options="$estadoCivils" 
                                :selected="old('estado_civil_id', $selected ?? '')"
                            />




                            <x-select-otro 
                                name="afp_id" 
                                label="AFP <span class='text-danger'>*</span>" 
                                :options="$afps" 
                                :selected="$empleado->afp_id ?? null" 
                            />



                            <x-select-otro 
                                name="salud_id" 
                                label="Salud <span class='text-danger'>*</span>" 
                                :options="$saluds" 
                                :selected="$empleado->salud_id ?? null" 
                            />


                            <x-select-otro 
                                name="turno_id" 
                                label="Turno <span class='text-danger'>*</span>" 
                                :options="$turnos" 
                                :selected="$empleado->turno_id ?? null" 
                            />

                            <div class="form-group">
                                <label for="hijos">Hijos</label>
                                <br>
                                <button type="button" class="btn btn-outline-primary btn-block" data-toggle="modal" data-target="#hijosModal">
                                    Gestionar Hijos
                                </button>
                                @if ($errors->has('hijos'))
                                    <span class="text-danger">{{ $errors->first('hijos') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="tallas">Tallas</label>
                                <br>
                                <button type="button" class="btn btn-outline-primary btn-block" data-toggle="modal" data-target="#tallasModal">
                                    Gestionar Tallas
                                </button>
                                @if ($errors->has('tallas'))
                                    <span class="text-danger">{{ $errors->first('tallas') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ url('/empleados') }}" class="btn btn-primary mt-3">
            <i class="fas fa-arrow-left"></i> Regresar al listado de empleados
        </a>
        <div class="form-group text-center mt-4">
            <button type="submit" class="btn btn-primary">{{ $esCreacion ? 'Crear' : 'Actualizar' }} Empleado</button>
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
