<?php

namespace App\Http\Requests\EstadoCivil;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstadoCivilRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255|unique:estado_civils,Nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre del estado civil es obligatorio.',
            'Nombre.unique' => 'Este estado civil ya está registrado.',
            'Nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        ];
    }



}
