<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Banco;
use App\Models\Comuna;
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
        $empresas = Empresa::with(['banco', 'comuna.region'])->get(); // Incluye relaciones
        return view('empresas.index', compact('empresas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $bancos = Banco::all();
        $comunas = Comuna::all();
        return view('empresas.create', compact('bancos', 'comunas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmpresaRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = uniqid() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('logos'), $logoName);
            $data['logo'] = 'logos/' . $logoName;
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
        $bancos = Banco::all();
        $comunas = Comuna::all();
        return view('empresas.edit',compact('empresa', compact('empresa', 'bancos', 'comunas')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmpresaRequest $request, Empresa $empresa)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($empresa->logo) {
                $oldPath = public_path($empresa->logo);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $logo = $request->file('logo');
            $logoName = uniqid() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('logos'), $logoName);
            $data['logo'] = 'logos/' . $logoName;
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
