<?php

namespace App\Http\Requests\Empresas;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpresaRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255|unique:empresas,Nombre',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'giro' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'cta_corriente' => 'required|string|max:50',
            'mail_formalizado' => 'required|email|max:255',
            'banco_id' => 'nullable|exists:bancos,id',
            'comuna_id' => 'nullable|exists:comunas,id',
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

            'giro.required' => 'El giro de la empresa es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            'cta_corriente.required' => 'La cuenta corriente es obligatoria.',
            'mail_formalizado.required' => 'El correo formalizado es obligatorio.',
            'mail_formalizado.email' => 'Debe ingresar un correo electrónico válido.',
            'banco_id.exists' => 'El banco seleccionado no es válido.',
            'comuna_id.exists' => 'La comuna seleccionada no es válida.',
        ];
    }



}
