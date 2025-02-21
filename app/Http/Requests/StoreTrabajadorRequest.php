<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TipoVestimenta;

class StoreTrabajadorRequest extends FormRequest
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
            'Rut' => 'required|unique:trabajadors,Rut|max:255',
            'Nombre' => 'required|string|max:255',
            'SegundoNombre' => 'nullable|string|max:255',
            'TercerNombre' => 'nullable|string|max:255',
            'ApellidoPaterno' => 'required|string|max:255',
            'ApellidoMaterno' => 'required|string|max:255',
            'FechaNacimiento' => 'required|date',
            // 'Correo' => 'required|email|max:255',
            'CorreoPersonal' => 'required|email|max:255',  // Nuevo campo para el correo personal, opcional
            'Foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Casino' => 'required|string|max:255',
            'ContratoFirmado' => 'required|string|max:255',
            'AnexoContrato' => 'required|string|max:255',
            'empresa_id' => 'required|exists:empresas,id',
            'cargo_id' => 'required|in:otro,' . \App\Models\Cargo::pluck('id')->implode(','),

            'situacion_id' => 'required|in:otro,' . implode(',', \App\Models\Situacion::pluck('id')->toArray()), // Nueva validación
            'estado_civil_id' => 'required|in:otro,' . \App\Models\EstadoCivil::pluck('id')->implode(','), // Añadir esta línea
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
                // 'salud_id' => 'required|exists:saluds,id',
            'salud_id' => 'required|in:otro,' . \App\Models\Salud::pluck('id')->implode(','),

            'sistema_trabajo_id' => 'required|in:otro,' . implode(',', \App\Models\SistemaTrabajo::pluck('id')->toArray()),

            'turno_id' => 'required|in:otro,' . \App\Models\Turno::pluck('id')->implode(','),
            'tallas' => 'required|array',
            'tallas.*.talla' => 'required|string|max:10',
            'salario_bruto' => 'required|numeric|min:0',
            'calle' => 'required|string|max:255',
            'numero_celular' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]+$/'],
            'nombre_emergencia' => 'required|string|max:255', // Ahora obligatorio
            'contacto_emergencia' => 'required|string|max:15', // Ahora obligatorio
            'fecha_inicio_trabajo' => 'required|date',
            'fecha_inicio_contrato' => 'required|date',

            'banco' => 'required|string|max:255', // Ahora obligatorio
            'numero_cuenta' => 'required|string|max:255', // Ahora obligatorio
            'tipo_cuenta' => 'required|string|max:255', // Ahora obligatorio
            
            'Rut_Empresa' => 'required|string|max:255', // Ahora obligatorio

            'id_jefe' => 'required|exists:jefes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'Rut.required' => 'El RUT es obligatorio.',
            'Rut.unique' => 'El RUT ingresado ya existe en el sistema.',

            'Nombre.required' => 'El nombre es obligatorio.',
            'ApellidoPaterno.required' => 'El apellido paterno es obligatorio.',
            'ApellidoMaterno.required' => 'El apellido materno es obligatorio.',

            'FechaNacimiento.required' => 'Debe ingresar una fecha de nacimiento.',
            'FechaNacimiento.date' => 'La fecha de nacimiento no es válida.',

            'CorreoPersonal.required' => 'El correo personal es obligatorio.',  // Nuevo mensaje
            'CorreoPersonal.email' => 'Debe ingresar un correo personal válido.',
            'CorreoPersonal.max' => 'El correo personal no puede exceder los 255 caracteres.',
            'Foto.image' => 'El archivo debe ser una imagen válida.',
            'Foto.mimes' => 'La imagen debe estar en formato jpeg, png, jpg, o gif.',
            'Casino.required' => 'El campo de casino es obligatorio.',
            'ContratoFirmado.required' => 'El contrato firmado es obligatorio.',
            'AnexoContrato.required' => 'El anexo de contrato es obligatorio.',
            'empresa_id.required' => 'Debe seleccionar una empresa válida.',
            'cargo_id.required' => 'Debe seleccionar un cargo válido.',
            'situacion_id.required' => 'Debe seleccionar una situación válida.',
            'estado_civil_id.required' => 'Debe seleccionar un estado civil válido.',
            'comuna_id.required' => 'Debe seleccionar una comuna válida.',
            'afp_id.required' => 'Debe seleccionar una AFP válida.',
            'salud_id.required' => 'Debe seleccionar un sistema de salud válido.',
            'sistema_trabajo_id.required' => 'Debe seleccionar un sistema de trabajo válido.',
            'turno_id.required' => 'Debe seleccionar un turno válido.',
            'tallas.required' => 'Debe ingresar las tallas.',
            'tallas.*.talla.required' => 'Debe ingresar una talla válida.',
            'salario_bruto.required' => 'Debe ingresar un salario bruto.',
            'salario_bruto.numeric' => 'El salario bruto debe ser un valor numérico.',
            'calle.required' => 'La dirección es obligatoria.',
            'numero_celular.required' => 'El número de celular es obligatorio.',
            'numero_celular.regex' => 'El número de celular debe tener un formato válido.',
            'fecha_inicio_trabajo.required' => 'Debe ingresar la fecha de inicio de trabajo.',

            'fecha_inicio_contrato.required' => 'Debe ingresar la fecha de inicio de su contrato',
            'fecha_inicio_contrato.date'=>'La fecha de inicio de contrato no es válida',



            'banco.required' => 'El campo Banco es obligatorio.', // Nuevo mensaje
            'banco.string' => 'El campo Banco debe ser una cadena de texto.',
            'banco.max' => 'El nombre del Banco no puede exceder los 255 caracteres.',

            'numero_cuenta.required' => 'El campo Número de cuenta es obligatorio.', // Nuevo mensaje
            'numero_cuenta.string' => 'El campo Número de Cuenta debe ser una cadena de texto.',
            'numero_cuenta.max' => 'El Número de Cuenta no puede exceder los 255 caracteres.',

            'tipo_cuenta.required' => 'El campo Tipo de cuenta es obligatorio.', // Nuevo mensaje
            'tipo_cuenta.string' => 'El campo Tipo de Cuenta debe ser una cadena de texto.',
            'tipo_cuenta.max' => 'El Tipo de Cuenta no puede exceder los 255 caracteres.',

            'Rut_Empresa.required' => 'El campo Rut de la Empresa es obligatorio.', // Nuevo mensaje
            'Rut_Empresa.string' => 'El campo Rut de la Empresa debe ser una cadena de texto.',
            'Rut_Empresa.max' => 'El Rut de la empresa no puede exceder los 255 caracteres.',

            'nombre_emergencia.required' => 'El campo nombre de emergencia es obligatorio.', // Nuevo mensaje
            'contacto_emergencia.required' => 'El campo contacto de emergencia es obligatorio.', // Nuevo mensaje

            'id_jefe.required' => 'El campo jefe es obligatorio.',
            'id_jefe.exists' => 'El jefe seleccionado no es válido.',
        ];
    }


    public function attributes(): array
    {
        $tipoVestimentas = TipoVestimenta::all();
        $attributes = [];

        foreach ($tipoVestimentas as $tipoVestimenta) {
            $attributes["tallas.{$tipoVestimenta->id}.talla"] = "talla de {$tipoVestimenta->Nombre}";
        }

        return $attributes;
    }
}
