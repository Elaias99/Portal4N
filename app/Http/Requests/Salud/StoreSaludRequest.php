<?php

namespace App\Http\Requests\Salud;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaludRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255|unique:saluds,Nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre del sistema de salud es obligatorio.',
            'Nombre.unique' => 'Este sistema de salud ya está registrado.',
            'Nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        ];
    }

}
