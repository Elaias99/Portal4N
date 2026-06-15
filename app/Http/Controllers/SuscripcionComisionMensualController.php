<?php

namespace App\Http\Controllers;

use App\Models\Asignaciones;
use App\Models\SuscripcionComisionMensual;
use App\Models\SuscripcionProveedor;
use App\Models\SuscripcionTransportista;
use App\Models\SuscripcionCantidadMensual;
use App\Services\Suscripciones\SuscripcionGeneracionMensualService;
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

        $asignacionesCantidadMensual = Asignaciones::with([
            'suscripcionProveedor.cobranzaCompra',
            'transportista',
        ])
        ->where('generar_automaticamente', 0)
        ->whereRaw("UPPER(TRIM(codigo)) NOT LIKE '%.COM'")
        ->whereRaw("UPPER(TRIM(codigo)) NOT LIKE '%COMISION%'")
        ->orderBy('codigo')
        ->get();

        return view('suscripciones.comisiones_mensuales.create', compact(
            'anio',
            'mes',
            'proveedores',
            'transportistas',
            'asignacionesCantidadMensual'
        ));
    }





    public function store(Request $request, SuscripcionGeneracionMensualService $generacionMensualService)
    {
        $data = $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',

            'cantidad_mensual_asignacion_id' => 'nullable|required_with:cantidad_mensual_cantidad|exists:suscripcion_asignaciones,id',
            'cantidad_mensual_cantidad' => 'nullable|required_with:cantidad_mensual_asignacion_id|integer|min:1',
            'cantidad_mensual_observacion' => 'nullable|string|max:1000',

            'suscripcion_proveedor_id' => 'required|exists:suscripcion_proveedores,id',
            'suscripcion_transportista_id' => 'required|exists:suscripcion_transportistas,id',

            'punto_1' => 'nullable|string|max:255',
            'origen_gasto' => 'nullable|string|max:255',
            'punto_2' => 'nullable|string|max:255',
            'servicio' => 'nullable|string|max:255',

            'costo' => 'required|integer|min:0',
            'cantidad' => 'required|integer|min:1',
            'observacion' => 'nullable|string|max:1000',
        ]);

        $anio = (int) $data['anio'];
        $mes = (int) $data['mes'];

        $codigoComision = 'COMISION';

        $debeGuardarCantidadMensual = !empty($data['cantidad_mensual_asignacion_id'])
            && !empty($data['cantidad_mensual_cantidad']);

        if ($debeGuardarCantidadMensual) {
            $existeCantidadMensual = SuscripcionCantidadMensual::where('suscripcion_asignacion_id', $data['cantidad_mensual_asignacion_id'])
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->exists();

            if ($existeCantidadMensual) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cantidad_mensual_asignacion_id' => 'Esta cantidad mensual ya existe para la asignación, año y mes seleccionado.',
                    ]);
            }
        }

        $existeComision = SuscripcionComisionMensual::where('anio', $anio)
            ->where('mes', $mes)
            ->whereRaw('UPPER(TRIM(codigo)) = ?', [$codigoComision])
            ->whereHas('asignacion', function ($query) use ($data) {
                $query->where('suscripcion_proveedor_id', $data['suscripcion_proveedor_id'])
                    ->where('suscripcion_transportista_id', $data['suscripcion_transportista_id']);
            })
            ->exists();

        if ($existeComision) {
            return back()
                ->withInput()
                ->withErrors([
                    'suscripcion_proveedor_id' => 'Esta comisión ya existe para el proveedor, transportista, año y mes seleccionado.',
                ]);
        }

        $costoComision = (int) $data['costo'];
        $cantidadComision = (int) $data['cantidad'];
        $totalComision = $costoComision * $cantidadComision;

        $grupoPrefactura = Asignaciones::query()
            ->where('suscripcion_proveedor_id', $data['suscripcion_proveedor_id'])
            ->where('suscripcion_transportista_id', $data['suscripcion_transportista_id'])
            ->whereNotNull('grupo_prefactura')
            ->whereRaw("TRIM(grupo_prefactura) <> ''")
            ->orderBy('id')
            ->value('grupo_prefactura');

        DB::transaction(function () use (
            $data,
            $anio,
            $mes,
            $codigoComision,
            $debeGuardarCantidadMensual,
            $costoComision,
            $cantidadComision,
            $totalComision,
            $grupoPrefactura
        ) {
            if ($debeGuardarCantidadMensual) {
                $asignacionCantidad = Asignaciones::findOrFail($data['cantidad_mensual_asignacion_id']);

                $codigoCantidad = $asignacionCantidad->codigo;
                $costoCantidad = (int) $asignacionCantidad->costo;
                $cantidadMensual = (int) $data['cantidad_mensual_cantidad'];
                $totalCantidad = $costoCantidad * $cantidadMensual;

                SuscripcionCantidadMensual::create([
                    'suscripcion_asignacion_id' => $asignacionCantidad->id,
                    'anio' => $anio,
                    'mes' => $mes,
                    'codigo' => $codigoCantidad,
                    'costo' => $costoCantidad,
                    'cantidad' => $cantidadMensual,
                    'total' => $totalCantidad,
                    'observacion' => $data['cantidad_mensual_observacion'] ?? null,
                ]);
            }

            $asignacionComision = Asignaciones::create([
                'suscripcion_proveedor_id' => (int) $data['suscripcion_proveedor_id'],
                'suscripcion_transportista_id' => (int) $data['suscripcion_transportista_id'],
                'punto_1' => $data['punto_1'] ?? null,
                'origen_gasto' => $data['origen_gasto'] ?? 'Suscripciones',
                'punto_2' => $data['punto_2'] ?? null,
                'codigo' => $codigoComision,
                'servicio' => $data['servicio'] ?? 'Comisión mensual',
                'costo' => $costoComision,
                'grupo_prefactura' => $grupoPrefactura,
                'generar_automaticamente' => 0,
            ]);

            SuscripcionComisionMensual::create([
                'suscripcion_asignacion_id' => $asignacionComision->id,
                'anio' => $anio,
                'mes' => $mes,
                'codigo' => $codigoComision,
                'costo' => $costoComision,
                'cantidad' => $cantidadComision,
                'total' => $totalComision,
                'observacion' => $data['observacion'] ?? null,
            ]);
        });

        $resultado = $generacionMensualService->generar($anio, $mes);

        $mensaje = "Datos registrados correctamente. Mes generado correctamente. Creados: {$resultado['creados']}.";

        if (($resultado['cantidades_creadas'] ?? 0) > 0) {
            $mensaje .= " Cantidades variables agregadas: {$resultado['cantidades_creadas']}.";
        }

        if (($resultado['comisiones_creadas'] ?? 0) > 0) {
            $mensaje .= " Comisiones agregadas: {$resultado['comisiones_creadas']}.";
        }

        if ($resultado['duplicados'] > 0) {
            $mensaje .= " Registros ya existentes no duplicados: {$resultado['duplicados']}.";
        }

        if (($resultado['cantidades_duplicadas'] ?? 0) > 0) {
            $mensaje .= " Cantidades variables ya existentes no duplicadas: {$resultado['cantidades_duplicadas']}.";
        }

        if (($resultado['comisiones_duplicadas'] ?? 0) > 0) {
            $mensaje .= " Comisiones ya existentes no duplicadas: {$resultado['comisiones_duplicadas']}.";
        }

        if ($resultado['opv_sin_rutas']->isNotEmpty()) {
            $mensaje .= ' No se generaron las siguientes rutas OPV porque no tienen locales OPV asignados: ';
            $mensaje .= $resultado['opv_sin_rutas']->unique()->implode('; ') . '.';
        }

        return redirect()
            ->route('suscripciones.liquidacion-detalles.index', [
                'anio' => $anio,
                'mes' => $mes,
            ])
            ->with('success', $mensaje);
    }



}