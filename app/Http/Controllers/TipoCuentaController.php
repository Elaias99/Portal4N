<?php

namespace App\Http\Controllers;

use App\Models\TipoCuenta;
use Illuminate\Http\Request;

class TipoCuentaController extends Controller
{
    public function index(Request $request)
    {

        $search = $request->input('search');
        $query = TipoCuenta::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");

        }

        $tipo_cuentas = $query->get();

        // $tipo_cuentas = TipoCuenta::all();

        return view('tipo_cuentas.index', compact('tipo_cuentas'));
    }

    public function create()
    {
        $tipo_cuenta = new TipoCuenta();
        return view('tipo_cuentas.create', compact('tipo_cuenta'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        TipoCuenta::create($validated);

        return redirect()->route('tipo_cuentas.index')->with('success', 'Tipo cuenta registrada exitosamente.');
    }

    public function edit(TipoCuenta $tipo_cuenta)
    {
        return view('tipo_cuentas.edit', compact('tipo_cuenta'));
    }

    public function update(Request $request, TipoCuenta $tipo_cuenta)
    {
        $validated = $request->validate([
            'nombre' => 'required|string'
        ]);

        $tipo_cuenta->update($validated);

        return redirect()->route('tipo_cuentas.index')->with('success', 'Tipo cuenta actualizada exitosamente.');
    }

    public function destroy(TipoCuenta $tipo_cuenta)
    {
        $tipo_cuenta->delete();

        return redirect()->route('tipo_cuentas.index')->with('success', 'Registro eliminado exitosamente.');
    }
}
