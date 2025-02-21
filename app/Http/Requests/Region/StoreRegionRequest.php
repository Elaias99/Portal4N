<?php

namespace App\Http\Requests\Region;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegionRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255|unique:regions,Nombre',
            'Numero' => 'required|integer|min:1|unique:regions,Numero',
        ];
    }

    public function messages(): array
    {
        return [
            'Nombre.required' => 'El nombre de la región es obligatorio.',
            'Nombre.unique' => 'Esta región ya está registrada.',
            'Numero.required' => 'El número de la región es obligatorio.',
            'Numero.integer' => 'El número de la región debe ser un valor entero.',
            'Numero.min' => 'El número de la región debe ser al menos 1.',
            'Numero.unique' => 'Este número de región ya está registrado.',
        ];
    }




}
