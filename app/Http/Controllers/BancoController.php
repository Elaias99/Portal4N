<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use Illuminate\Http\Request;

class BancoController extends Controller
{
    //
    public function index(Request $request){

        $search = $request->input('search');
        $query = Banco::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");

        }



        $bancos = $query->get();
        return view('bancos.index', compact('bancos'));

    }

    public function create()
    {
        return view('bancos.create');
    }


    public function store (Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        Banco::create($validated);

        return redirect()->route('bancos.index')->with('success', 'Banco registrado exitosamente.');

    }


    public function edit(Banco $banco)
    {
        //
        return view('bancos.edit', compact('banco'));
    }

    public function update(Request $request, Banco $banco)
    {
        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        $banco->update($validated);



        return redirect()->route('bancos.index')->with('success', 'Banco actualizada exitosamente.');

    }


    public function destroy(Banco $banco)
    {
        $banco->delete();
        return redirect()->route('bancos.index')->with('success', 'Registro eliminado exitosamente.');


    }



}
