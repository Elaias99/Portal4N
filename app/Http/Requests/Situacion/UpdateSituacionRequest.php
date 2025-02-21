<?php

namespace App\Http\Requests\Situacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSituacionRequest extends FormRequest
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
                Rule::unique('situacions', 'Nombre')->ignore($this->route('situacion')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre de la situación es obligatorio.',
            'Nombre.unique' => 'Esta situación ya está registrada.',
            'Nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        ];
    }


}
