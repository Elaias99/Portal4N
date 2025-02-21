<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Exports\FacturasExport;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\EstadoFacturaActualizado;
use Maatwebsite\Excel\Facades\Excel;

class FacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Factura::with(['proveedor', 'empresa']); // Incluir las relaciones

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('empresa', 'like', '%' . $request->search . '%')
                ->orWhere('glosa', 'like', '%' . $request->search . '%')
                ->orWhereHas('proveedor', function ($q2) use ($request) {
                    $q2->where('razon_social', 'like', '%' . $request->search . '%')
                        ->orWhere('rut', 'like', '%' . $request->search . '%');
                })
                ->orWhereHas('empresa', function ($q3) use ($request) { // Buscar por empresa
                    $q3->where('Nombre', 'like', '%' . $request->search . '%');
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $facturas = $query->get();
        return view('facturas.index', compact('facturas'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $proveedores = Proveedor::all();
        $empresas = Empresa::all(); // Cargar empresas
        $proveedorSeleccionado = $request->query('proveedor_id');
        return view('facturas.create', compact('proveedores', 'empresas', 'proveedorSeleccionado'));
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'centro_costo' => 'required|string',
            'empresa_id' => 'required|exists:empresas,id', // Validación de empresa
            'glosa' => 'required|string',
            'pagador' => 'required|string',
            'tipo_documento' => 'required|string', // Añadir validación
            'comentario' => 'required|string',
            'status' => 'required|in:Pendiente,Pagado,Abonado,No Pagar',
        ]);

        Factura::create($validated);

        return redirect()->route('facturas.index')->with('success', 'Factura creada exitosamente.');
    }


    public function updateStatus(Request $request, $id)
    {
        // Validar el nuevo estado
        $request->validate([
            'status' => 'required|in:Pendiente,Pagado,Abonado,No Pagar'
        ]);

        // Encontrar la factura y actualizar el estado
        $factura = Factura::findOrFail($id);
        $factura->status = $request->input('status');
        $factura->save();

        // Notificar a los administradores
        $admins = User::role('admin')->get(); // Usuarios con rol 'admin'
        foreach ($admins as $admin) {
            $admin->notify(new EstadoFacturaActualizado($factura));
        }

        // Redirigir al listado de facturas con un mensaje de éxito
        return redirect()->route('facturas.index')->with('success', 'Estado de la factura actualizado correctamente.');
    }

    //Mostrar el detalle de cada factura al momento que fue modificada
    public function showFacturaDetail($id)
    {
        $factura = Factura::with(['proveedor', 'empresa'])->findOrFail($id);
        return view('facturas.detail', compact('factura'));
    }

    //Exportar la tabla de facturas en un formato Excel
    public function export()
    {
        return Excel::download(new FacturasExport, 'facturas.xlsx');
    }



}
