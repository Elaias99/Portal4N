<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Empresas\StoreEmpresaRequest;
use App\Http\Requests\Empresas\UpdateEmpresaRequest;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $empresas=Empresa::all();
        return view('empresas.index',compact('empresas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('empresas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmpresaRequest $request)
    {
        //
        $data = $request->validated();

        // Verifica si se subió un archivo
        if ($request->hasFile('logo')) {
            // Almacena el archivo en la carpeta 'logos' dentro de 'storage/app/public'
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Empresa::create($data);

        return redirect()->route('empresas.index')->with('success', 'Empresa creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Empresa $empresa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa)
    {
        //
        return view('empresas.edit',compact('empresa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmpresaRequest $request, Empresa $empresa)
    {
        //
        $data = $request->validated();

        // Verifica si se subió un nuevo archivo
        if ($request->hasFile('logo')) {
            // Elimina el logo anterior si existe
            if ($empresa->logo) {
                Storage::delete('public/' . $empresa->logo);
            }

            // Almacena el nuevo archivo
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $empresa->update($data);

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa)
    {
        //
        $empresa->delete();

        return redirect()->route('empresas.index')->with('success', 'Empresa eliminado exitosamente.');
    }
}
