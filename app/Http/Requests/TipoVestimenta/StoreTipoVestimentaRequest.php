<?php

namespace App\Http\Requests\TipoVestimenta;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoVestimentaRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255|unique:tipo_vestimentas,Nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre del tipo de vestimenta es obligatorio.',
            'Nombre.unique' => 'Este tipo de vestimenta ya está registrado.',
        ];
    }


}
