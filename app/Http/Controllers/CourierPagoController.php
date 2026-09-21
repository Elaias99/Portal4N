<?php

namespace App\Http\Controllers;

use App\Models\CourierImportacion;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierCatalogoService;
use App\Services\Courier\CourierPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * Raíz del módulo Courier: el proceso de pago del mes.
 * Los catálogos (agentes, comunas, proveedores) viven en
 * CourierCatalogoController.
 */
class CourierPagoController extends Controller
{
    public function __construct(
        private readonly CourierPagoService $pago,
        private readonly CourierCatalogoService $catalogo
    ) {
    }

    public function index(Request $request): View
    {
        $periodos = $this->pago->periodos();
        $periodo = $this->pago->periodoActual($request->input('periodo'), $periodos);

        /*
         * ?importacion=ID muestra el resultado de esa carga (la recién
         * hecha, o una del historial). Los pesajes se cargan de a varios
         * archivos, así que acepta una lista: ?importacion=12,13,14.
         * Solo cargas del período mostrado.
         */
        $mostradas = collect();

        if ($periodo && $request->filled('importacion')) {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', explode(',', (string) $request->input('importacion')))
            )));

            if ($ids !== []) {
                $mostradas = CourierImportacion::query()
                    ->with('usuario:id,name')
                    ->where('courier_periodo_id', $periodo->id)
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->get();
            }
        }

        return view('courier.index', [
            'periodos' => $periodos,
            'periodo' => $periodo,
            'alertas' => $periodo ? $this->pago->alertas($periodo) : null,
            'importaciones' => $periodo ? $this->pago->importaciones($periodo) : collect(),
            'mostradas' => $mostradas,
            'mesSugerido' => $periodo
                ? sprintf('%04d-%02d', $periodo->anio, $periodo->mes)
                : now()->format('Y-m'),
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    public function importarGeolice(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            [
                'archivo' => ['required', 'file', 'extensions:xlsx', 'max:40960'],
                'periodo' => ['required', 'date_format:Y-m'],
            ],
            [
                'archivo.required' => 'Elige la descarga de Geolice (archivo .xlsx).',
                'archivo.file' => 'El archivo no se recibió completo; inténtalo de nuevo.',
                'archivo.extensions' => 'El archivo debe ser el .xlsx que entrega Geolice.',
                'archivo.max' => 'El archivo supera el tamaño permitido (40 MB).',
                'periodo.required' => 'Indica el mes de pago.',
                'periodo.date_format' => 'El mes de pago no tiene un formato válido.',
            ]
        );

        $codigo = str_replace('-', '', $datos['periodo']);

        $existente = CourierPeriodo::query()->where('codigo', $codigo)->first();

        if ($existente?->estaCerrado()) {
            return back()
                ->withErrors(['periodo' => "El período {$existente->nombre} está cerrado; no se puede cargar sobre él."])
                ->withInput();
        }

        try {
            $importacion = $this->pago->importarGeolice(
                $request->file('archivo'),
                $codigo,
                $request->user()?->id
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['archivo' => 'Falló la importación, no se guardó nada: ' . $e->getMessage()])
                ->withInput();
        }

        return redirect()->route('courier.index', [
            'periodo' => $codigo,
            'importacion' => $importacion->id,
        ]);
    }

    public function importarPesajes(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            [
                'archivos' => ['required', 'array', 'min:1', 'max:31'],
                'archivos.*' => ['file', 'extensions:csv,txt', 'max:20480'],
                'periodo' => ['required', 'date_format:Y-m'],
            ],
            [
                'archivos.required' => 'Elige uno o más CSV de pesajes de bodega.',
                'archivos.max' => 'Se pueden cargar hasta 31 archivos a la vez (un mes).',
                'archivos.*.file' => 'Uno de los archivos no se recibió completo; inténtalo de nuevo.',
                'archivos.*.extensions' => 'Los pesajes deben ser archivos .csv como los entrega bodega.',
                'archivos.*.max' => 'Uno de los archivos supera el tamaño permitido (20 MB).',
                'periodo.required' => 'Indica el mes de pago.',
                'periodo.date_format' => 'El mes de pago no tiene un formato válido.',
            ]
        );

        $codigo = str_replace('-', '', $datos['periodo']);

        $existente = CourierPeriodo::query()->where('codigo', $codigo)->first();

        if ($existente?->estaCerrado()) {
            return back()
                ->withErrors(['periodo' => "El período {$existente->nombre} está cerrado; no se puede cargar sobre él."])
                ->withInput();
        }

        try {
            $registros = $this->pago->importarPesajes(
                $request->file('archivos'),
                $codigo,
                $request->user()?->id
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['archivos' => 'Falló la importación, no se guardó nada: ' . $e->getMessage()])
                ->withInput();
        }

        return redirect()->route('courier.index', [
            'periodo' => $codigo,
            'importacion' => $registros->pluck('id')->implode(','),
        ]);
    }
}
