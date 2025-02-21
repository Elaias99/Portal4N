<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TipoVestimenta;
use Illuminate\Validation\Rule;

class UpdateTrabajadorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'Rut' => [
                'required',
                'max:255',
                Rule::unique('trabajadors', 'Rut')->ignore($this->route('empleado')),
            ],
            'Nombre' => 'required|string|max:255',
            'SegundoNombre' => 'nullable|string|max:255',
            'TercerNombre' => 'nullable|string|max:255',
            'ApellidoPaterno' => 'required|string|max:255',
            'ApellidoMaterno' => 'required|string|max:255',
            'FechaNacimiento' => 'required|date',

            'CorreoPersonal' => 'nullable|email|max:255',  // Nuevo campo para el correo personal, opcional
            'Foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Casino' => 'required|string|max:255',
            'ContratoFirmado' => 'required|string|max:255',
            'AnexoContrato' => 'required|string|max:255',
            'empresa_id' => 'required|exists:empresas,id',

            'cargo_id' => 'required|in:otro,' . \App\Models\Cargo::pluck('id')->implode(','),


            'situacion_id' => 'required|in:otro,' . implode(',', \App\Models\Situacion::pluck('id')->toArray()), // Nueva validación


            
            'estado_civil_id' => 'required|in:otro,' . \App\Models\EstadoCivil::pluck('id')->implode(','),
            'comuna_id' => 'required|exists:comunas,id',



            'afp_id' => [
            'required',
            function ($attribute, $value, $fail) {
                if ($value === 'otro') {
                    if (!request()->filled('nuevo_afp')) {
                        $fail('Debe proporcionar un nombre para la nueva AFP.');
                    }
                    if (!request()->filled('tasa_cotizacion')) {
                        $fail('Debe proporcionar la tasa de cotización para la nueva AFP.');
                    }
                    if (!request()->filled('tasa_sis')) {
                        $fail('Debe proporcionar la tasa SIS para la nueva AFP.');
                    }
                } else {
                    // Asegurarse de que el valor de afp_id existe en la tabla a_f_p_s
                    if (!\App\Models\AFP::where('id', $value)->exists()) {
                        $fail('La AFP seleccionada no es válida.');
                    }
                }
            }
        ],
            'tasa_cotizacion' => 'nullable|numeric|min:0', // Solo se valida cuando se selecciona "Otro"
            'tasa_sis' => 'nullable|numeric|min:0',



            'salud_id' => 'required|in:otro,' . \App\Models\Salud::pluck('id')->implode(','),

            'sistema_trabajo_id' => 'required|in:otro,' . implode(',', \App\Models\SistemaTrabajo::pluck('id')->toArray()), // Nueva validación
            'turno_id' => 'required|in:otro,' . \App\Models\Turno::pluck('id')->implode(','), // Validación del turno
            'tallas.*.talla' => 'nullable|string|max:10',
            'salario_bruto' => 'required|numeric|min:0',
            'calle' => 'required|string|max:255',
            'numero_celular' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]+$/'],
            'nombre_emergencia' => 'nullable|string|max:255',
            'contacto_emergencia' => 'nullable|string|max:15',
            'fecha_inicio_trabajo' => 'required|date',
            'fecha_inicio_contrato' => 'required|date',
            // otras reglas de validación...
            'banco' => 'nullable|string|max:255',
            'numero_cuenta' => 'nullable|string|max:255',
            'tipo_cuenta' => 'nullable|string|max:255',
            'Rut_Empresa' => 'nullable|string|max:255',

            'id_jefe' => 'required|exists:jefes,id',

        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        $tipoVestimentas = TipoVestimenta::all();
        $attributes = [];

        foreach ($tipoVestimentas as $tipoVestimenta) {
            $attributes["tallas.{$tipoVestimenta->id}.talla"] = "talla de {$tipoVestimenta->Nombre}";
        }

        return $attributes;
    }



    /**
     * Mensajes personalizados para validación.
     */
    public function messages(): array
    {
        return [
            'Rut.required' => 'El RUT es obligatorio.',
            'Rut.unique' => 'El RUT ya está registrado.',
            'Nombre.required' => 'El nombre es obligatorio.',
            'ApellidoPaterno.required' => 'El apellido paterno es obligatorio.',
            'ApellidoMaterno.required' => 'El apellido materno es obligatorio.',
            'FechaNacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'FechaNacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',

            'CorreoPersonal.email' => 'Debe ingresar un correo personal válido.',  // Mensaje para CorreoPersonal
            'CorreoPersonal.max' => 'El correo personal no puede exceder los 255 caracteres.',  // Mensaje para CorreoPersonal
            'Foto.image' => 'El archivo debe ser una imagen.',
            'Foto.mimes' => 'La imagen debe ser un archivo de tipo: jpeg, png, jpg, gif.',
            'Foto.max' => 'La imagen no debe ser mayor de 2MB.',
            'empresa_id.required' => 'Debe seleccionar una empresa.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'cargo_id.required' => 'Debe seleccionar un cargo.',
            'cargo_id.exists' => 'El cargo seleccionado no es válido.',
            'situacion_id.required' => 'Debe seleccionar una situación.',
            'situacion_id.exists' => 'La situación seleccionada no es válida.',
            'estado_civil_id.required' => 'Debe seleccionar un estado civil.',
            'estado_civil_id.exists' => 'El estado civil seleccionado no es válido.',
            'comuna_id.required' => 'Debe seleccionar una comuna.',
            'comuna_id.exists' => 'La comuna seleccionada no es válida.',
            'afp_id.required' => 'Debe seleccionar una AFP.',
            'afp_id.exists' => 'La AFP seleccionada no es válida.',
            'salud_id.required' => 'Debe seleccionar una salud.',
            'salud_id.exists' => 'La salud seleccionada no es válida.',
            'sistema_trabajo_id.required' => 'Debe seleccionar un sistema de trabajo.',
            'sistema_trabajo_id.exists' => 'El sistema de trabajo seleccionado no es válido.',
            'turno_id.required' => 'Debe seleccionar un turno.',
            'turno_id.exists' => 'El turno seleccionado no es válido.',
            'tallas.*.talla.max' => 'La talla no debe exceder de 10 caracteres.',
            'salario_bruto.required' => 'El salario bruto es obligatorio.',
            'salario_bruto.numeric' => 'El salario bruto debe ser un número.',
            'salario_bruto.min' => 'El salario bruto no puede ser negativo.',
            'calle.required' => 'La calle es obligatoria.',
            'numero_celular.required' => 'El número de celular es obligatorio.',
            'numero_celular.regex' => 'El formato del número de celular es inválido.',
            'nombre_emergencia.max' => 'El nombre de emergencia no debe exceder de 255 caracteres.',
            'contacto_emergencia.max' => 'El contacto de emergencia no debe exceder de 15 caracteres.',

            'fecha_inicio_trabajo.required' => 'La fecha de inicio de trabajo es obligatoria.',
            'fecha_inicio_trabajo.date' => 'La fecha de inicio de trabajo debe ser una fecha válida.',

            'fecha_inicio_contrato.required' => 'Debe ingresar la fecha de inicio de su contrato',
            'fecha_inicio_contrato.date'=>'La fecha de inicio de contrato no es válida',

            'banco.string' => 'El campo Banco debe ser una cadena de texto.',
            'banco.max' => 'El nombre del Banco no puede exceder los 255 caracteres.',
            
            'numero_cuenta.string' => 'El campo Número de Cuenta debe ser una cadena de texto.',
            'numero_cuenta.max' => 'El Número de Cuenta no puede exceder los 255 caracteres.',

            'tipo_cuenta.string' => 'El campo Tipo de Cuenta debe ser una cadena de texto.',
            'tipo_cuenta.max' => 'El Tipo de Cuenta no puede exceder los 255 caracteres.',

            'Rut_Empresa.string' => 'El campo Rut de la Empresa debe ser una cadena de texto.',
            'Rut_Empresa.max' => 'El Rut de la empresa no puede exceder los 255 caracteres.',

            'id_jefe.required' => 'El campo jefe es obligatorio.',
            'id_jefe.exists' => 'El jefe seleccionado no es válido.',
        ];
    }








}

