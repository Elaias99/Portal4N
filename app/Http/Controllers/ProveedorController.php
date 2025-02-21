<?php

namespace App\Http\Controllers;

use App\Exports\ProveedorExport;
use Illuminate\Http\Request;
use App\Models\Proveedor;
use Maatwebsite\Excel\Facades\Excel;

class ProveedorController extends Controller
{
    /**
     * Mostrar la lista de proveedores.
     */
    public function index()
    {
        $proveedores = Proveedor::all();
        return view('proveedores.index', compact('proveedores'));
    }

    /**
     * Mostrar el formulario para crear un nuevo proveedor.
     */
    public function create()
    {
        return view('proveedores.create');
    }

    /**
     * Guardar un nuevo proveedor en la base de datos.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            // Campos requeridos
            'razon_social' => 'required|string|max:255',
            'rut' => 'required|unique:proveedores,rut|max:12',
            'telefono_empresa' => 'required|string|max:15',
            'giro_comercial' => 'required|string|max:255',
            'banco' => 'required|string|max:255',
            'tipo_cuenta' => 'required|string|max:255',
            'nro_cuenta' => 'required|string|max:20',
            'tipo_pago' => 'required|string|max:255',
    
            // Campos opcionales
            'direccion_facturacion' => 'nullable|string|max:255',
            'direccion_despacho' => 'nullable|string|max:255',

            'comuna_empresa' => 'nullable|string|max:255',
    
            // Representante Legal
            'Nombre_RepresentanteLegal' => 'nullable|string|max:255',
            'Rut_RepresentanteLegal' => 'nullable|unique:proveedores,Rut_RepresentanteLegal|max:12',
            'Telefono_RepresentanteLegal' => 'nullable|string|max:15',
            'Correo_RepresentanteLegal' => 'nullable|email|max:255',
    
            // Contacto Empresa 1
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => 'nullable|string|max:15',
            'contacto_correo' => 'nullable|email|max:255',
    
            // Contacto Empresa 2
            'nombre_contacto2' => 'nullable|string|max:255',
            'telefono_contacto2' => 'nullable|string|max:15',
            'correo_contacto2' => 'nullable|email|max:255',
    
            // Datos bancarios adicionales
            'correo_banco' => 'nullable|email|max:255',
            'nombre_razon_social_banco' => 'nullable|string|max:255',
            'rut_banco' => 'nullable|string|max:12',

            'cargo_contacto1' => 'nullable|string|max:255',
            'cargo_contacto2' => 'nullable|string|max:255',
        ]);
        
        

        Proveedor::create($validatedData);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor creado con éxito.');
    }

    /**
     * Mostrar el formulario para editar un proveedor.
     */
    public function edit($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        return view('proveedores.edit', compact('proveedor'));
    }

    /**
     * Actualizar un proveedor en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            // Campos requeridos
            'razon_social' => 'required|string|max:255',
            'rut' => 'required|unique:proveedores,rut,' . $id,
            'telefono_empresa' => 'required|string|max:15',
            'giro_comercial' => 'required|string|max:255',
            'banco' => 'required|string|max:255',
            'tipo_cuenta' => 'required|string|max:255',
            'nro_cuenta' => 'required|string|max:20',
            'tipo_pago' => 'required|string|max:255',
    
            // Campos opcionales
            'direccion_facturacion' => 'nullable|string|max:255',
            'direccion_despacho' => 'nullable|string|max:255',
                                                                                                                                                                              
            'comuna_empresa' => 'nullable|string|max:255',
    
            // Representante Legal
            'Nombre_RepresentanteLegal' => 'nullable|string|max:255',
            'Rut_RepresentanteLegal' => 'nullable|unique:proveedores,Rut_RepresentanteLegal,' . $id,
            'Telefono_RepresentanteLegal' => 'nullable|string|max:15',
            'Correo_RepresentanteLegal' => 'nullable|email|max:255',
    
            // Contacto Empresa 1
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => 'nullable|string|max:15',
            'contacto_correo' => 'nullable|email|max:255',
    
            // Contacto Empresa 2
            'nombre_contacto2' => 'nullable|string|max:255',
            'telefono_contacto2' => 'nullable|string|max:15',
            'correo_contacto2' => 'nullable|email|max:255',
    
            // Datos bancarios adicionales
            'correo_banco' => 'nullable|email|max:255',
            'nombre_razon_social_banco' => 'nullable|string|max:255',
            'rut_banco' => 'nullable|string|max:12',

            'cargo_contacto1' => 'nullable|string|max:255',
            'cargo_contacto2' => 'nullable|string|max:255',
        ]);
        

        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($validatedData);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado con éxito.');
    }

    /**
     * Eliminar un proveedor de la base de datos.
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado con éxito.');
    }

    public function export()
    {
        
        return Excel::download(new ProveedorExport, 'proveedores.xlsx');

    }

}
