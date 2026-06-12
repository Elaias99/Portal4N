<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionProveedor;
use App\Models\SuscripcionTransportista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuscripcionComisionMensualController extends Controller
{




    public function create(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes = (int) $request->input('mes', now()->month);

        $proveedores = SuscripcionProveedor::with('cobranzaCompra')
            ->whereHas('cobranzaCompra')
            ->get()
            ->sortBy(fn ($proveedor) => $proveedor->cobranzaCompra?->razon_social)
            ->values();

        $transportistas = SuscripcionTransportista::query()
            ->orderBy('nombre_transportista')
            ->get();

        return view('suscripciones.comisiones_mensuales.create', compact(
            'anio',
            'mes',
            'proveedores',
            'transportistas'
        ));
    }





    public function store(Request $request)
    {
        $data = $request->validate([
            'suscripcion_proveedor_id' => 'required|exists:suscripcion_proveedores,id',
            'suscripcion_transportista_id' => 'required|exists:suscripcion_transportistas,id',
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',

            'punto_1' => 'nullable|string|max:255',
            'origen_gasto' => 'nullable|string|max:255',
            'punto_2' => 'nullable|string|max:255',





            'servicio' => 'nullable|string|max:255',

            'costo' => 'required|integer|min:0',
            'cantidad' => 'required|integer|min:1',
            'observacion' => 'nullable|string|max:1000',
        ]);

        $codigo = 'COMISION';

        $esCodigoComision = str_ends_with($codigo, '.COM')
            || str_contains($codigo, 'COMISION');

        if (!$esCodigoComision) {
            return back()
                ->withInput()
                ->withErrors([
                    'codigo' => 'El código debe corresponder a una comisión. Ejemplo: AM.COM o COMISION.',
                ]);
        }

        $existe = SuscripcionComisionMensual::where('anio', $data['anio'])
            ->where('mes', $data['mes'])
            ->whereRaw('UPPER(TRIM(codigo)) = ?', [$codigo])
            ->whereHas('asignacion', function ($query) use ($data) {
                $query->where('suscripcion_proveedor_id', $data['suscripcion_proveedor_id'])
                    ->where('suscripcion_transportista_id', $data['suscripcion_transportista_id']);
            })
            ->exists();

        if ($existe) {
            return back()
                ->withInput()
                ->withErrors([
                    'codigo' => 'Esta comisión ya existe para el proveedor, transportista, año y mes seleccionado.',
                ]);
        }

        $costo = (int) $data['costo'];
        $cantidad = (int) $data['cantidad'];
        $total = $costo * $cantidad;

        $grupoPrefactura = Asignaciones::query()
            ->where('suscripcion_proveedor_id', $data['suscripcion_proveedor_id'])
            ->where('suscripcion_transportista_id', $data['suscripcion_transportista_id'])
            ->whereNotNull('grupo_prefactura')
            ->whereRaw("TRIM(grupo_prefactura) <> ''")
            ->orderBy('id')
            ->value('grupo_prefactura');

        DB::transaction(function () use ($data, $codigo, $costo, $cantidad, $total, $grupoPrefactura) {
            $asignacion = Asignaciones::create([
                'suscripcion_proveedor_id' => (int) $data['suscripcion_proveedor_id'],
                'suscripcion_transportista_id' => (int) $data['suscripcion_transportista_id'],
                'punto_1' => $data['punto_1'] ?? null,
                'origen_gasto' => $data['origen_gasto'] ?? 'Suscripciones',
                'punto_2' => $data['punto_2'] ?? null,
                'codigo' => $codigo,
                'servicio' => $data['servicio'] ?? 'Comisión mensual',
                'costo' => $costo,
                'grupo_prefactura' => $grupoPrefactura,
                'generar_automaticamente' => 0,
            ]);

            SuscripcionComisionMensual::create([
                'suscripcion_asignacion_id' => $asignacion->id,
                'anio' => (int) $data['anio'],
                'mes' => (int) $data['mes'],
                'codigo' => $codigo,
                'costo' => $costo,
                'cantidad' => $cantidad,
                'total' => $total,
                'observacion' => $data['observacion'] ?? null,
            ]);
        });

        return redirect()
            ->route('suscripciones.liquidacion-detalles.index', [
                'anio' => $data['anio'],
                'mes' => $data['mes'],
            ])
            ->with('success', 'Comisión mensual registrada correctamente.');
    }




}