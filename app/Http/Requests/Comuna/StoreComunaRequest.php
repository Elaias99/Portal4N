<?php

namespace App\Http\Requests\Comuna;

use Illuminate\Foundation\Http\FormRequest;

class StoreComunaRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255|unique:comunas,Nombre',
            'region_id' => 'required|exists:regions,id',
        ];
    }




    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre de la comuna es obligatorio.',
            'Nombre.unique' => 'Esta comuna ya está registrada.',
            'region_id.required' => 'La región es obligatoria.',
            'region_id.exists' => 'La región seleccionada no es válida.',
        ];
    }






}
