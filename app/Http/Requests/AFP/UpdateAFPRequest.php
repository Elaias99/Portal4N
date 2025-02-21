<?php

namespace App\Http\Requests\AFP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAFPRequest extends FormRequest
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
            'Nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('a_f_p_s', 'Nombre')->ignore($this->route('afp')),
            ],
            'tasa_cotizacion' => 'required|numeric|min:0|max:100',
            'tasa_sis' => 'required|numeric|min:0|max:100',
        ];
    }


    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre de la AFP es obligatorio.',
            'Nombre.unique' => 'Esta AFP ya está registrada.',
            'tasa_cotizacion.required' => 'La tasa de cotización es obligatoria.',
            'tasa_sis.required' => 'La tasa SIS es obligatoria.',
            'tasa_cotizacion.numeric' => 'La tasa de cotización debe ser un número.',
            'tasa_sis.numeric' => 'La tasa SIS debe ser un número.',
            'tasa_cotizacion.min' => 'La tasa de cotización no puede ser negativa.',
            'tasa_sis.min' => 'La tasa SIS no puede ser negativa.',
            'tasa_cotizacion.max' => 'La tasa de cotización no puede ser mayor a 100.',
            'tasa_sis.max' => 'La tasa SIS no puede ser mayor a 100.',
        ];
    }





}
