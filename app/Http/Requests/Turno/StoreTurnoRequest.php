<?php

namespace App\Http\Requests\Turno;

use Illuminate\Foundation\Http\FormRequest;

class StoreTurnoRequest extends FormRequest
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
            'nombre' => 'required|string|max:255|unique:turnos,nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del turno es obligatorio.',
            'nombre.unique' => 'Este turno ya está registrado.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        ];
    }


}
