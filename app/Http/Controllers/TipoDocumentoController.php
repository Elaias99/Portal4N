<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    //
    public function index (Request $request)
    {

        $search = $request->input('search');
        $query = TipoDocumento::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");

        }

        $tipo_documentos = $query->get();

        return view('tipo_documentos.index', compact('tipo_documentos'));

    }


    public function create ()
    {

        $tipo_documento = new TipoDocumento();

        return view('tipo_documentos.create', compact('tipo_documento'));

    }



    public function store (Request $request)
    {

        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        TipoDocumento::create($validated);

        return redirect()->route('tipo_documentos.index')->with('success', 'Tipo documento registrada exitosamente.');

    }



    public function edit (TipoDocumento $tipo_documento)
    {

        return view('tipo_documentos.edit', compact('tipo_documento'));

    }



    public function update (Request $request, TipoDocumento $tipo_documento)
    {

        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        $tipo_documento->update($validated);

        return redirect()->route('tipo_documentos.index')->with('success', 'Tipo documento actualizada exitosamente.');



    }



    public function destroy(TipoDocumento $tipo_documento)
    {

        $tipo_documento->delete();

        return redirect()->route('tipo_documentos.index')->with('success', 'Registro eliminado exitosamente.');   

    }






























}
