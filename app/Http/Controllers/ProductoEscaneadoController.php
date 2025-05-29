<?php

namespace App\Http\Controllers;

use App\Models\ProductoEscaneado;
use App\Models\ProductoBase;
use Illuminate\Http\Request;

class ProductoEscaneadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos = ProductoEscaneado::latest()->get();
        $mensaje = session('mensaje');
        return view('escaneo', compact('productos', 'mensaje'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $codigo = trim($request->input('codigo'));


        $mensaje = null;
        $producto = ProductoBase::where('codigo', $codigo)->first();

        if (!$producto) {
            $mensaje = "Producto no registrado en la base local: $codigo";
        } else {
            // Si el producto existe, registrar escaneo si no fue registrado antes
            $yaEscaneado = ProductoEscaneado::where('codigo', $codigo)->exists();

            if ($yaEscaneado) {
                $mensaje = "Código ya escaneado: $codigo";
            } else {
                ProductoEscaneado::create(['codigo' => $codigo]);
                $mensaje = "Código guardado: $codigo";
            }
        }

        $productos = ProductoEscaneado::latest()->get();

        return view('escaneo', [
            'productos' => $productos,
            'mensaje' => $mensaje,
            'productoInfo' => $producto, // pasamos los datos completos del producto
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductoEscaneado $productoEscaneado)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductoEscaneado $productoEscaneado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductoEscaneado $productoEscaneado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductoEscaneado $productoEscaneado)
    {
        //
    }
}
