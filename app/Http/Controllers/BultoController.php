<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bultos;

class BultoController extends Controller
{
    // Método para listar los últimos 10 bultos
    public function index()
    {
        $bultos = Bultos::orderBy('fecha_carga', 'desc')->take(10)->get();
        return view('bultos.index', compact('bultos'));
    }

    // Método para mostrar los detalles de un bulto específico
    public function show($id)
    {
        $bulto = Bultos::findOrFail($id);
        return view('bultos.show', compact('bulto'));
    }
}
