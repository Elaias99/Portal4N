<?php

namespace App\Http\Requests\Empresas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpresaRequest extends FormRequest
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
                Rule::unique('empresas', 'Nombre')->ignore($this->route('empresa')),
            ],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre de la empresa es obligatorio.',
            'Nombre.unique' => 'Esta empresa ya está registrada.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.mimes' => 'El logo debe estar en formato jpeg, png, jpg o gif.',
            'logo.max' => 'El logo no debe exceder los 2 MB.',
        ];
    }
}
