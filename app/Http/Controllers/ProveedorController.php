<?php

namespace App\Http\Controllers;

use App\Exports\ProveedorExport;
use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\TipoCuenta;
use App\Models\TipoDocumento;
use App\Models\Banco;
use App\Models\Comuna;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PlantillaProveedoresExport;
use Illuminate\Support\Facades\Schema;

class ProveedorController extends Controller
{
    /**
     * Mostrar la lista de proveedores.
     */
    public function index(Request $request)
    {
        $query = Proveedor::with(['banco', 'comuna', 'tipoCuenta', 'tipoPago']);

        // Filtros activos
        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'LIKE', '%' . $request->razon_social . '%');
        }

        if ($request->filled('rut')) {
            $query->where('rut', 'LIKE', '%' . $request->rut . '%');
        }

        if ($request->filled('banco')) {
            $query->where('banco_id', $request->banco);
        }

        if ($request->filled('tipo_cuenta')) {
            $query->where('tipo_cuenta_id', $request->tipo_cuenta);
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago_id', $request->tipo_pago);
        }

        $proveedores = $query->orderBy('razon_social', 'asc')->paginate(10);


        $columnas = Schema::getColumnListing('proveedores');

        $excluir = ['id', 'created_at', 'updated_at', 'banco_id', 'tipo_cuenta_id', 'tipo_pago_id', 'comuna_id'];
        $columnasFiltradas = collect($columnas)
            ->reject(fn($col) => in_array($col, $excluir))
            ->mapWithKeys(function ($col) {
                return [$col => ucwords(str_replace('_', ' ', str_replace('nro', 'N°', $col)))];
            })
            ->toArray();

        // Agregar manualmente relaciones
        $columnasFiltradas['banco'] = 'Banco';
        $columnasFiltradas['tipo_cuenta'] = 'Tipo de Cuenta';
        $columnasFiltradas['tipo_pago'] = 'Tipo de Documento';
        $columnasFiltradas['comuna'] = 'Comuna';

        $camposOpcionales = $columnasFiltradas;



        return view('proveedores.index', [
            'proveedores' => $proveedores,
            'bancos' => Banco::all(),
            'comunas' => Comuna::all(), // opcionalmente lo podrías quitar si ya no lo usás en la vista
            'tiposCuenta' => TipoCuenta::all(),
            'tiposDocumento' => TipoDocumento::all(),
            'camposOpcionales' => $camposOpcionales,
        ]);
    }



    

    /**
     * Mostrar el formulario para crear un nuevo proveedor.
     */
    public function create()
    {

        $bancos = Banco::all();
        $tiposCuentas = TipoCuenta::all();
        $tiposPagos = TipoDocumento::all(); // 🔹 Obtener todos los tipos de pago
        $comunas = Comuna::all();

        return view('proveedores.create', compact('bancos', 'tiposCuentas', 'tiposPagos','comunas'));

    }



    /**
     * Guardar un nuevo proveedor en la base de datos.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            // Campos requeridos
            'razon_social' => 'required|string|max:255',//obligaotrio
            'rut' => 'required|string|max:12',//obligaotrio

            'telefono_empresa' => 'required|string|max:15',//obligaotrio
            'giro_comercial' => 'required|string|max:255',//obligaotrio
            'banco_id' => 'required|exists:bancos,id',//obligaotrio

            'tipo_cuenta_id' => 'required|exists:tipo_cuentas,id',//obligaotrio

            'nro_cuenta' => 'required|string|max:20',//obligaotrio
            'tipo_pago_id' => 'required|exists:tipo_documentos,id',
    
            // Campos opcionales
            'direccion_facturacion' => 'required|string|max:255',//obligaotrio
            'direccion_despacho' => 'required|string|max:255',//obligaotrio
                                                                                                                                                                              
            'comuna_id' => 'required|exists:comunas,id',//obligaotrio

    
            // Representante Legal
            'Nombre_RepresentanteLegal' => 'nullable|string|max:255',

            'Rut_RepresentanteLegal' => 'nullable|string|max:255',



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
            'correo_banco' => 'required|email|max:255',//obligaotrio
            'nombre_razon_social_banco' => 'nullable|string|max:255',
            // 'rut_banco' => 'required|string|max:12',

            'cargo_contacto1' => 'nullable|string|max:255',//obligaotrio
            'cargo_contacto2' => 'nullable|string|max:255',//obligaotrio
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
        $bancos = Banco::all();
        $tiposCuentas = TipoCuenta::all();
        $tiposPagos = TipoDocumento::all(); // 🔹 Obtener todos los tipos de pago
        $comunas = Comuna::all();

        return view('proveedores.edit', compact('proveedor', 'bancos', 'tiposCuentas', 'tiposPagos', 'comunas'));


    }


    /**
     * Actualizar un proveedor en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            // Campos requeridos
            'razon_social' => 'required|string|max:255', //obligaotrio
            'rut' => 'required|string|max:12', //obligaotrio

            'telefono_empresa' => 'required|string|max:15',//obligaotrio
            'giro_comercial' => 'required|string|max:255',//obligaotrio

            'banco_id' => 'required|exists:bancos,id',//obligaotrio

            'tipo_cuenta_id' => 'required|exists:tipo_cuentas,id',//obligaotrio

            'nro_cuenta' => 'required|string|max:20',//obligaotrio
            'tipo_pago_id' => 'required|exists:tipo_documentos,id',
    
            // Campos opcionales
            'direccion_facturacion' => 'required|string|max:255',//obligaotrio
            'direccion_despacho' => 'required|string|max:255',//obligaotrio
                                                                                                                                                                              
            'comuna_id' => 'required|exists:comunas,id',//obligaotrio

    
            // Representante Legal
            'Nombre_RepresentanteLegal' => 'nullable|string|max:255',//opcionales

            'Rut_RepresentanteLegal' => 'nullable|string|max:255',//opcionales



            'Telefono_RepresentanteLegal' => 'nullable|string|max:15',//opcionales
            'Correo_RepresentanteLegal' => 'nullable|email|max:255',//opcionales
    
            // Contacto Empresa 1
            'contacto_nombre' => 'nullable|string|max:255',//opcionales
            'contacto_telefono' => 'nullable|string|max:15',//opcionales
            'contacto_correo' => 'nullable|email|max:255',//opcionales
    
            // Contacto Empresa 2
            'nombre_contacto2' => 'nullable|string|max:255',//opcionales
            'telefono_contacto2' => 'nullable|string|max:15',//opcionales
            'correo_contacto2' => 'nullable|email|max:255',//opcionales
    
            // Datos bancarios adicionales
            'correo_banco' => 'required|email|max:255',//obligaotrio
            'nombre_razon_social_banco' => 'nullable|string|max:255',//opcionales

            'cargo_contacto1' => 'nullable|string|max:255',//opcionales
            'cargo_contacto2' => 'nullable|string|max:255',//opcionales
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

    public function export(Request $request)
    {
        $opcionales = $request->input('opciones', []);

        return Excel::download(new ProveedorExport($opcionales), 'proveedores.xlsx');
    }




    public function descargarPlantilla()
    {
        return Excel::download(new PlantillaProveedoresExport, 'plantilla_proveedores.xlsx');
    }

    public function mostrarFormularioExport()
    {
        $camposOpcionales = [
            'banco' => 'Banco',
            'tipo_cuenta' => 'Tipo de Cuenta',
            'tipo_pago' => 'Tipo de Documento',
        ];

        return view('proveedores.modal_exportar_proveedores', compact('camposOpcionales'));
    }


}
