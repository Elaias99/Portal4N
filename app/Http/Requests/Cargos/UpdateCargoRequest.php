<?php

namespace App\Http\Requests\Cargos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCargoRequest extends FormRequest
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
                Rule::unique('cargos', 'Nombre')->ignore($this->route('cargo')),
            ],
        ];
    }





    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre del cargo es obligatorio.',
            'Nombre.unique' => 'Este cargo ya está registrado.',
            // Otros mensajes personalizados si es necesario
        ];
    }












}
