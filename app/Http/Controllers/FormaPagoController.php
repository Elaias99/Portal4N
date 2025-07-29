<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormaPago;

class FormaPagoController extends Controller
{
    //
    public function index(Request $request)
    {

        $search = $request->input('search');
        $query = FormaPago::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");

        }



        $forma_pagos = $query->get();

        return view('forma_pagos.index', compact('forma_pagos'));

    }

    public function create()
    {


        $forma_pago = new FormaPago();

        return view('forma_pagos.create', compact('forma_pago'));

    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        FormaPago::create($validated);

        return redirect()->route('forma_pagos.index')->with('success', 'Forma de Pago registrado exitosamente.');


        
    }

    public function edit (FormaPago $forma_pago)
    {

        return view('forma_pagos.edit', compact('forma_pago'));

    }


    public function update(Request $request, FormaPago $forma_pago)
    {

        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        $forma_pago->update($validated);

        return redirect()->route('forma_pagos.index')->with('success', 'Forma Pago actualizado exitosamente.');

    }


    public function destroy(FormaPago $forma_pago)
    {

        $forma_pago->delete();

        return redirect()->route('forma_pagos.index')->with('success', 'Registro eliminado exitosamente.');   

    }




}
