<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Models\PlazoPago;
use App\Models\CentroCosto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\FormaPago;
use Illuminate\Support\Facades\Log;
use App\Models\TipoDocumento;
use App\Models\Banco;
use App\Models\TipoCuenta;
use App\Imports\CompraImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedoresFaltantesExport;
use Illuminate\Support\Str;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\PlantillaComprasExport; // Asegúrate de importar esto

use App\Exports\CompraExport;



class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Compra::with(['user', 'empresa', 'proveedor']);

        // Detectar año y mes más reciente registrados en compras
        $ultimaCompra = Compra::orderBy('año', 'desc')
            ->orderByRaw("FIELD(mes, 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre') DESC")
            ->first();

        $mesActivo = $request->filled('month') ? $request->month : ($ultimaCompra->mes ?? null);
        $anioActivo = $request->filled('year') ? $request->year : ($ultimaCompra->año ?? null);


        // Filtros
        if ($request->filled('year')) {
            $query->where('año', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('mes', $request->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ Buscador por razon_social del proveedor
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('proveedor', function ($q) use ($search) {
                $q->where('razon_social', 'like', '%' . $search . '%');
            });
        }

        // Filtro por RUT del proveedor
        if ($request->filled('rut')) {
            $rut = $request->rut;
            $query->whereHas('proveedor', function ($q) use ($rut) {
                $q->where('rut', 'like', '%' . $rut . '%');
            });
        }

        // Paginación
        $compras = $query
            ->orderBy('año', 'desc')
            ->orderByRaw("FIELD(mes, 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre') DESC")
            ->paginate(10);

        $camposOpcionales = [
            'empresa' => 'Empresa',
            'rut' => 'RUT',
            'proveedor' => 'Proveedor',
            'centro_costo' => 'Centro de Costo',
            'glosa' => 'Glosa',
            'observacion' => 'Observación',
            'tipo_de_documento' => 'Tipo de Documento',
            'plazo_pago' => 'Plazo de Pago',
            'forma_pago' => 'Forma de Pago',
            'pago_total' => 'Pago Total',
            'fecha_vencimiento' => 'Fecha de Vencimiento',
            'año' => 'Año',
            'mes' => 'Mes',
            'fecha_documento' => 'Fecha Documento',
            'numero_documento' => 'N° Documento',
            'oc' => 'Orden de Compra',
            'status' => 'Estado',
            'usuario' => 'Usuario',
            'archivo_oc' => 'Archivo OC',
            'archivo_documento' => 'Archivo Documento',
        ];



        // Proveedores para posibles otros filtros
        $proveedores = Proveedor::all();
        $bancos = Banco::all();
        $tipoCuentas = TipoCuenta::all();
        $formasPago = FormaPago::all();
        $plazosPago = PlazoPago::all();
        $tiposDocumento = TipoDocumento::all();
        $empresas = Empresa::all();
        $centrosCostos = CentroCosto::all();


        return view('compras.index', compact(
            'compras',
            'proveedores',
            'bancos',
            'tipoCuentas',
            'formasPago',
            'plazosPago',
            'tiposDocumento',
            'empresas',
            'centrosCostos',
            'mesActivo',
            'anioActivo',
            'camposOpcionales',
        ));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $plazosPago = PlazoPago::all();
        $proveedores = Proveedor::with('tipoPago')->get();
        $empresas = Empresa::all();
        $centrosCosto = CentroCosto::all();
        $tiposPagos = TipoDocumento::all();
        $formasPago = FormaPago::all(); // 🔹 Obtener las formas de pago

        return view('compras.create', compact('proveedores', 'empresas', 'tiposPagos', 'centrosCosto', 'formasPago', 'plazosPago'));
    }





    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // Si el usuario ingresó un nuevo centro de costo, lo creamos y lo asignamos a la compra
        if ($request->filled('nuevo_centro_costo')) {
            $centroCosto = CentroCosto::create(['nombre' => $request->nuevo_centro_costo]);
            $request->merge(['centro_costo_id' => $centroCosto->id]);
        }


        // Validar los datos del formulario
        $validatedData = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'centro_costo_id' => 'nullable|exists:centros_costos,id',
            'glosa' => 'required|string|max:1000',
            'observacion' => 'nullable|string|max:1000',
            // 'tipo_pago' => 'required|string|max:100',

            'forma_pago_id' => 'required|exists:forma_pago,id',

            'plazo_pago_id' => 'required|exists:plazo_pago,id',

            'pago_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'año' => 'required|integer|min:1900|max:2100',
            'mes' => 'required|string|max:50',
            'fecha_documento' => 'nullable|date',
            'numero_documento' => 'nullable|string|max:255',
            'oc' => 'nullable|string|max:255',
            'archivo_oc' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'archivo_documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png',

            'tipo_pago_id' => 'required|exists:tipo_documentos,id',


            'status' => 'required|in:Pendiente,Pagado,Abonado,No Pagar', // ✅ Agregado
        ]);

        // 🔐 Validación de duplicado
        $existe = Compra::where('tipo_pago_id', $validatedData['tipo_pago_id'])
        ->where('numero_documento', $validatedData['numero_documento'])
        ->exists();

        if ($existe) {
        return back()->withErrors([
            'numero_documento' => 'Ya existe una compra con ese número y tipo de documento.',
        ])->withInput();
        }

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
        $plazosPago = PlazoPago::all();
        $proveedores = Proveedor::with('tipoPago')->get();
        $empresas = Empresa::all();
        $centrosCosto = CentroCosto::all();
        $tiposPagos = TipoDocumento::all();
        $formasPago = FormaPago::all(); // 🔹 Obtener las formas de pago

        return view('compras.edit', compact('compra', 'proveedores', 'empresas', 'tiposPagos', 'centrosCosto', 'formasPago','plazosPago'));
    }







    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Compra $compra)
    {

        if ($request->filled('nuevo_centro_costo')) {
            $centroCosto = CentroCosto::create(['nombre' => $request->nuevo_centro_costo]);
            $request->merge(['centro_costo_id' => $centroCosto->id]);
        }
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'centro_costo_id' => 'nullable|exists:centros_costos,id',
            'glosa' => 'required|string|max:1000',
            'observacion' => 'nullable|string|max:1000',
            // 'tipo_pago' => 'required|string|max:100',

            'forma_pago_id' => 'required|exists:forma_pago,id',

            'plazo_pago_id' => 'required|exists:plazo_pago,id',



            'pago_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'año' => 'required|integer|min:1900|max:2100',
            'mes' => 'required|string|max:50',
            'fecha_documento' => 'nullable|date',
            'numero_documento' => 'nullable|string|max:255',
            'oc' => 'nullable|string|max:255',
            'archivo_oc' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'archivo_documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png',

            'tipo_pago_id' => 'required|exists:tipo_documentos,id',


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

    public function importar(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            // ✅ 1. Leer encabezados reales del archivo importado
            $archivo = $request->file('archivo_excel');
            $spreadsheet = IOFactory::load($archivo);
            $hoja = $spreadsheet->getActiveSheet();
            $encabezadosArchivo = $hoja->rangeToArray('A1:' . $hoja->getHighestColumn() . '1')[0];

            // ✅ 2. Obtener los campos esperados de la plantilla oficial
            $encabezadosEsperados = PlantillaComprasExport::campos();


            // ✅ 3. Comparar columnas esperadas vs archivo
            $faltantes = array_diff(
                array_map('strtolower', $encabezadosEsperados),
                array_map('strtolower', $encabezadosArchivo)
            );

            if (!empty($faltantes)) {
                return redirect()->route('compras.index')->with('faltantes_plantilla_compras', $faltantes);
            }

            // ✅ 4. Si todo bien, continuar con importación
            $import = new CompraImport;
            Excel::import($import, $archivo);

            if (count($import->proveedoresFaltantes) > 0) {
                session()->put('proveedores_faltantes', $import->proveedoresFaltantes);
            }

            return redirect()->route('compras.index')->with('import_result', [
                'importadas' => $import->importadas,
                'omitidas' => $import->omitidas,
                'errores' => $import->errores,
                'erroresDuplicados' => $import->erroresDuplicados,
                'erroresValidacion' => collect($import->errores)
                    ->reject(fn($e) => Str::contains($e, 'Duplicado'))
                    ->values(),
                'detalles' => $import->importadasDetalle,
                'hayErrores' => count($import->errores) > 0,
                'hayOmitidas' => $import->omitidas > 0,
                'hayIncompletos' => false,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar el archivo: ' . $e->getMessage());
        }
    }



    public function descargarPlantilla()
    {
        Log::info('📥 Se ejecutó el método descargarPlantilla');

        try {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PlantillaComprasExport, 'plantilla_compras.xlsx');
        } catch (\Throwable $e) {
            Log::error('❌ Error al generar la plantilla: ' . $e->getMessage());
            return response('Error al generar la plantilla. Revisa los logs.', 500);
        }
    }


    public function toggleImportante($id)
    {
        $compra = Compra::findOrFail($id);
        $compra->importante = !$compra->importante;
        $compra->save();

        return response()->json([
            'success' => true,
            'importante' => $compra->importante
        ]);
    }

    public function exportarProveedoresFaltantes()
    {
        $faltantes = session('proveedores_faltantes', []);

        if (empty($faltantes)) {
            return redirect()->route('compras.index')->with('error', 'No hay proveedores faltantes para exportar.');
        }

        // ✅ Limpiamos la sesión de proveedores faltantes después de exportar
        // session()->forget('proveedores_faltantes');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProveedoresFaltantesExport($faltantes),
            'proveedores_faltantes.xlsx'
        );
    }


    public function limpiarProveedoresFaltantes()
    {
        session()->forget('proveedores_faltantes');
        return redirect()->route('compras.index')->with('success', 'Lista de proveedores faltantes limpiada.');
    }


    public function export(Request $request)
    {
        $opciones = $request->input('opciones', []);

        // Lista de campos válidos
        $camposPermitidos = [
            'empresa', 'rut', 'proveedor', 'centro_costo', 'glosa', 'observacion',
            'tipo_de_documento', 'plazo_pago', 'forma_pago', 'pago_total',
            'fecha_vencimiento', 'año', 'mes', 'fecha_documento',
            'numero_documento', 'oc', 'status', 'usuario', 'archivo_oc', 'archivo_documento',
        ];

        // Validar y limpiar campos recibidos
        $opcionesValidas = array_values(array_intersect($opciones, $camposPermitidos));

        if (empty($opcionesValidas)) {
            return redirect()->back()->with('error', 'Debes seleccionar al menos un campo para exportar.');
        }

        return Excel::download(new CompraExport($opcionesValidas), 'compras.xlsx');
    }

















}
