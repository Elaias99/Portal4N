<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bultos;
use App\Models\Jefe;


class BultoController extends Controller
{
    // Método para listar los últimos 10 bultos
    public function index()
    { 
        // Obtener los últimos 10 bultos
        $bultos = Bultos::orderBy('fecha_carga', 'desc')->take(10)->get();

        // Obtener todos los jefes disponibles para asignar reclamos
        $jefes = Jefe::all();

        return view('bultos.index', compact('bultos', 'jefes'));
    }

    // Método para mostrar los detalles de un bulto específico
    public function show($id)
    {
        $bulto = Bultos::findOrFail($id);
        return view('bultos.show', compact('bulto'));
    }
}
