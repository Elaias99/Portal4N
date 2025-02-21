<?php

namespace App\Http\Requests\SistemaTrabajo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSistemaTrabajoRequest extends FormRequest
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
            //
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sistema_trabajos', 'nombre')->ignore($this->route('sistema_trabajo')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del sistema de trabajo es obligatorio.',
            'nombre.unique' => 'Este sistema de trabajo ya está registrado.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        ];
    }


}
