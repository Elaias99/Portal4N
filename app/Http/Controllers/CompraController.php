<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;



class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Crear una consulta base con las relaciones necesarias
        $query = Compra::with(['user', 'empresa', 'proveedor']);

        // Filtro por Año
        if ($request->filled('year')) {
            $query->where('año', $request->year);
        }

        // Filtro por Mes
        if ($request->filled('month')) {
            $query->where('mes', $request->month);
        }

        // Filtro por Proveedor
        if ($request->filled('provider')) {
            $query->whereHas('proveedor', function ($q) use ($request) {
                $q->where('razon_social', $request->provider);
            });
        }

        // Filtro por Tipo de Documento
        if ($request->filled('document_type')) {
            $query->where('tipo_documento', $request->document_type);
        }

        // Mantener la funcionalidad de búsqueda general
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('año', 'like', "%$search%")
                ->orWhere('mes', 'like', "%$search%")
                ->orWhere('tipo_documento', 'like', "%$search%")
                ->orWhere('centro_costo', 'like', "%$search%")
                ->orWhereHas('empresa', function ($q) use ($search) {
                    $q->where('Nombre', 'like', "%$search%");
                })
                ->orWhereHas('proveedor', function ($q) use ($search) {
                    $q->where('razon_social', 'like', "%$search%")
                        ->orWhere('rut', 'like', "%$search%");
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Obtener los resultados finales
        $compras = $query->get();

        // Obtener los proveedores para el filtro desplegable
        $proveedores = Proveedor::all();

        return view('compras.index', compact('compras', 'proveedores'));
    }





    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $proveedores = Proveedor::all();
        $empresas = Empresa::all();

        // Centros de Costo predeterminados
        $centrosCostoPredeterminados = ['Courier', 'Transporte', 'Suscripciones', 'Agencias'];

        // Obtener los centros de costo únicos desde las compras
        $centrosCostoDB = Compra::select('centro_costo')->distinct()->pluck('centro_costo')->toArray();

        // Fusionar los valores predefinidos con los de la base de datos, evitando duplicados
        $centrosCosto = array_unique(array_merge($centrosCostoPredeterminados, $centrosCostoDB));

        return view('compras.create', compact('proveedores', 'empresas', 'centrosCosto'));
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'centro_costo' => 'required|string|max:255',
            'glosa' => 'required|string|max:1000',
            'observacion' => 'nullable|string|max:1000',
            'tipo_pago' => 'required|string|max:100',
            'forma_pago' => 'required|string|max:100',
            'pago_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'año' => 'required|integer|min:1900|max:2100',
            'mes' => 'required|string|max:50',
            'fecha_documento' => 'nullable|date',
            'numero_documento' => 'nullable|string|max:255',
            'oc' => 'nullable|string|max:255',
            'archivo_oc' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'archivo_documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'tipo_documento'=> 'nullable|string|max:1000',
            'status' => 'required|in:Pendiente,Pagado,Abonado,No Pagar', // ✅ Agregado
        ]);

        // Agregar el ID del usuario autenticado
        $user = Auth::user();
        $validatedData['user_id'] = $user->id; // Usar el ID del usuario autenticado

        // Manejar los archivos adjuntos
        if ($request->hasFile('archivo_oc')) {
            $archivoOC = $request->file('archivo_oc');
            $nombreOC = $archivoOC->getClientOriginalName(); // Obtener el nombre original del archivo
            $validatedData['archivo_oc'] = $archivoOC->storeAs('ordenes_compra', $nombreOC); // Guardar con el nombre original
        }
        
        if ($request->hasFile('archivo_documento')) {
            $archivoDoc = $request->file('archivo_documento');
            $nombreDoc = $archivoDoc->getClientOriginalName(); // Obtener el nombre original del archivo
            $validatedData['archivo_documento'] = $archivoDoc->storeAs('documentos', $nombreDoc); // Guardar con el nombre original
        }
        

        // --- Bloque de integración para "Contado" ---
        if ($validatedData['tipo_pago'] === 'Contado' && $request->has('opcion_contado')) {
            // Si el usuario eligió la opción "viernes" se calcula el siguiente viernes a partir de la fecha del documento.
            // Si se eligió "hoy", se asigna la fecha del documento.
            $fechaDocumento = $validatedData['fecha_documento'] ?? date('Y-m-d');
            
            if ($request->opcion_contado === 'viernes') {
                $validatedData['fecha_vencimiento'] = calcularSiguienteViernes($fechaDocumento);
            } elseif ($request->opcion_contado === 'hoy') {
                $validatedData['fecha_vencimiento'] = $fechaDocumento;
            }
        }



        // Crear una nueva compra
        Compra::create($validatedData);

        // Redirigir con un mensaje de éxito
        return redirect()->route('compras.index')->with('success', 'Compra creada con éxito.');
    }




    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Compra $compra)
    {
        $proveedores = Proveedor::all();
        $empresas = Empresa::all();

        // Centros de Costo predeterminados
        $centrosCostoPredeterminados = ['Courier', 'Transporte', 'Suscripciones', 'Agencias'];

        // Obtener los centros de costo únicos desde las compras
        $centrosCostoDB = Compra::select('centro_costo')->distinct()->pluck('centro_costo')->toArray();

        // Fusionar los valores predefinidos con los de la base de datos, evitando duplicados
        $centrosCosto = array_unique(array_merge($centrosCostoPredeterminados, $centrosCostoDB));

        return view('compras.edit', compact('compra', 'proveedores', 'empresas', 'centrosCosto'));
    }




    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Compra $compra)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'centro_costo' => 'required|string|max:255',
            'glosa' => 'required|string|max:1000',
            'observacion' => 'nullable|string|max:1000',
            'tipo_pago' => 'required|string|max:100',
            'forma_pago' => 'required|string|max:100',
            'pago_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'año' => 'required|integer|min:1900|max:2100',
            'mes' => 'required|string|max:50',
            'fecha_documento' => 'nullable|date',
            'numero_documento' => 'nullable|string|max:255',
            'oc' => 'nullable|string|max:255',
            'archivo_oc' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'archivo_documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png',

            'tipo_documento'=> 'nullable|string|max:1000',
            'status' => 'required|in:Pendiente,Pagado,Abonado,No Pagar', // ✅ Agregado
        ]);

        // Manejar los archivos adjuntos
        if ($request->hasFile('archivo_oc')) {
            $archivoOC = $request->file('archivo_oc');
            $nombreOC = $archivoOC->getClientOriginalName(); // Obtener el nombre original del archivo
            $validatedData['archivo_oc'] = $archivoOC->storeAs('ordenes_compra', $nombreOC); // Guardar con el nombre original
        }
        
        if ($request->hasFile('archivo_documento')) {
            $archivoDoc = $request->file('archivo_documento');
            $nombreDoc = $archivoDoc->getClientOriginalName(); // Obtener el nombre original del archivo
            $validatedData['archivo_documento'] = $archivoDoc->storeAs('documentos', $nombreDoc); // Guardar con el nombre original
        }
        

        if ($validatedData['tipo_pago'] === 'Contado' && $request->has('opcion_contado')) {
            $fechaDocumento = $validatedData['fecha_documento'] ?? date('Y-m-d');
            
            if ($request->opcion_contado === 'viernes') {
                $validatedData['fecha_vencimiento'] = calcularSiguienteViernes($fechaDocumento);
            } elseif ($request->opcion_contado === 'hoy') {
                $validatedData['fecha_vencimiento'] = $fechaDocumento;
            }
        }

        // Actualizar la compra con los nuevos datos
        $compra->update($validatedData);

        // Redirigir con un mensaje de éxito
        return redirect()->route('compras.index')->with('success', 'Compra actualizada con éxito.');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Buscar la compra en la base de datos
        $compra = Compra::findOrFail($id);

        // Eliminar archivos adjuntos si existen
        if ($compra->archivo_oc) {
            Storage::delete($compra->archivo_oc);
        }
        if ($compra->archivo_documento) {
            Storage::delete($compra->archivo_documento);
        }

        // Eliminar la compra
        $compra->delete();

        // Redirigir con un mensaje de éxito
        return redirect()->route('compras.index')->with('success', 'Compra eliminada correctamente.');
    }





    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Función para que el admin pueda adjuntar en el registro de compras, adjunte un archivo O.C 
    public function descargarArchivoOC($id)
    {
        $compra = Compra::findOrFail($id);

        if ($compra->archivo_oc && Storage::exists($compra->archivo_oc)) {
            return response()->download(storage_path("app/" . $compra->archivo_oc), basename($compra->archivo_oc));
        }

        return redirect()->back()->with('error', 'El archivo de Orden de Compra no existe.');
    }

    // Función para que el admin pueda adjuntar en el registro de compras, adjunte un archivo de documento
    public function descargarArchivoDocumento($id)
    {
        $compra = Compra::findOrFail($id);

        if ($compra->archivo_documento && Storage::exists($compra->archivo_documento)) {
            return response()->download(storage_path("app/" . $compra->archivo_documento), basename($compra->archivo_documento));
        }

        return redirect()->back()->with('error', 'El archivo del Documento no existe.');
    }

    public function updateStatus(Request $request, $id)
    {
        // Buscar la compra
        $compra = Compra::findOrFail($id);

        // Validar que el estado sea uno de los permitidos
        $request->validate([
            'status' => 'required|in:Pendiente,Pagado,Abonado,No Pagar',
        ]);

        // Actualizar el estado
        $compra->status = $request->status;
        $compra->save();

        // Redirigir con un mensaje de éxito
        return redirect()->route('compras.index')->with('success', 'Estado actualizado correctamente.');
    }




}
