<?php

namespace App\Http\Controllers;

use App\Models\CourierImportacion;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierCalculoService;
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
        /*
         * "Elegir otro archivo": se descarta la revisión y su archivo,
         * para no dejarlo ocupando disco.
         */
        if ($request->boolean('nuevo')) {
            $this->pago->descartarRevision(session('revisionGeolice.token'));
            $request->session()->forget('revisionGeolice');
        }

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
            'resumenPago' => $periodo ? $this->pago->resumenPago($periodo) : null,
            'importaciones' => $periodo ? $this->pago->importaciones($periodo) : collect(),
            'mostradas' => $mostradas,
            'mesSugerido' => $periodo
                ? sprintf('%04d-%02d', $periodo->anio, $periodo->mes)
                : now()->format('Y-m'),
            'resumen' => $this->catalogo->resumen(),
        ]);
    }



    public function revisarGeolice(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            [
                'archivo' => ['required', 'file', 'extensions:xlsx,csv', 'max:40960'],
                'periodo' => ['required', 'date_format:Y-m'],
            ],
            [
                'archivo.required' => 'Elige la descarga de Geolice.',
                'archivo.file' => 'El archivo no se recibió completo; inténtalo de nuevo.',
                'archivo.extensions' => 'El archivo debe estar en formato .xlsx o .csv.',
                'archivo.max' => 'El archivo supera el tamaño permitido (40 MB).',
                'periodo.required' => 'Indica el mes de pago.',
                'periodo.date_format' => 'El mes de pago no tiene un formato válido.',
            ]
        );

        $codigo = str_replace('-', '', $datos['periodo']);

        // Si había otra revisión a medias, su archivo ya no sirve.
        $this->pago->descartarRevision(session('revisionGeolice.token'));

        try {
            $resultado = $this->pago->revisarGeolice(
                $request->file('archivo'),
                $codigo
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors([
                    'archivo' => 'No se pudo revisar el archivo: ' . $e->getMessage()
                ])
                ->withInput();
        }

        /*
         * Se guarda en sesión, no como dato de un solo uso: el usuario
         * puede recargar la página mientras decide si confirma.
         */
        $request->session()->put('revisionGeolice', $resultado);

        return back()->withInput();
    }

    /*
     * Cómo se reparten los bultos del período entre los agentes.
     * Tiene su propia pantalla para no cargar la portada.
     */
    public function distribucion(Request $request): View
    {
        $periodos = $this->pago->periodos();
        $periodo = $this->pago->periodoActual($request->input('periodo'), $periodos);

        return view('courier.distribucion', [
            'periodos' => $periodos,
            'periodo' => $periodo,
            'distribucion' => $periodo ? $this->pago->distribucion($periodo) : null,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    /*
     * Aplica la cadena de pago a los bultos del período. Se puede
     * repetir: reescribe el cálculo completo.
     */
    public function calcular(Request $request, CourierCalculoService $calculo): RedirectResponse
    {
        $datos = $request->validate(
            ['periodo' => ['required', 'regex:/^\d{4}(0[1-9]|1[0-2])$/']],
            ['periodo.required' => 'Indica el período a calcular.']
        );

        $periodo = CourierPeriodo::query()->where('codigo', $datos['periodo'])->first();

        if ($periodo === null) {
            return back()->withErrors(['periodo' => 'Ese período no existe.']);
        }

        if ($periodo->estaCerrado()) {
            return back()->withErrors(['periodo' => "El período {$periodo->nombre} está cerrado."]);
        }

        try {
            $resultado = $calculo->calcular($periodo);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'periodo' => 'Falló el cálculo, no se guardó nada: ' . $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('courier.pago', ['periodo' => $periodo->codigo])
            ->with('calculoListo', $resultado['resumen']);
    }

    /*
     * El detalle de lo que se puede pagar: por zona, por tipo de pago y
     * por proveedor, con IVA. Es la pantalla que mira Finanzas.
     */
    public function pago(Request $request): View
    {
        $periodos = $this->pago->periodos();
        $periodo = $this->pago->periodoActual($request->input('periodo'), $periodos);

        return view('courier.pago', [
            'periodos' => $periodos,
            'periodo' => $periodo,
            'resumenPago' => $periodo ? $this->pago->resumenPago($periodo) : null,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    /*
     * Todo lo que el período tiene sin resolver, con las listas
     * completas (en la portada solo se muestra el conteo).
     */
    public function pendientes(Request $request): View
    {
        $periodos = $this->pago->periodos();
        $periodo = $this->pago->periodoActual($request->input('periodo'), $periodos);

        return view('courier.pendientes', [
            'periodos' => $periodos,
            'periodo' => $periodo,
            'alertas' => $periodo ? $this->pago->alertas($periodo) : null,
            'resumen' => $this->catalogo->resumen(),
        ]);
    }

    /*
     * Confirma la revisión: recién aquí se guardan los bultos, leyendo
     * el archivo que quedó en disco durante la revisión.
     */
    public function confirmarGeolice(Request $request): RedirectResponse
    {
        $revision = session('revisionGeolice');

        if (! is_array($revision) || empty($revision['token'])) {
            return redirect()
                ->route('courier.index')
                ->withErrors(['archivo' => 'No hay una revisión pendiente; vuelve a cargar el archivo.']);
        }

        $existente = CourierPeriodo::query()
            ->where('codigo', $revision['periodo'])
            ->first();

        if ($existente?->estaCerrado()) {
            return back()->withErrors([
                'periodo' => "El período {$existente->nombre} está cerrado; no se puede cargar sobre él.",
            ]);
        }

        try {
            $importacion = $this->pago->confirmarGeolice(
                $revision['token'],
                $request->user()?->id
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'archivo' => 'No se pudo importar el archivo, no se guardó nada: ' . $e->getMessage(),
            ]);
        }

        $request->session()->forget('revisionGeolice');

        return redirect()->route('courier.index', [
            'periodo' => $revision['periodo'],
            'importacion' => $importacion->id,
        ]);
    }




    public function importarGeolice(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            [
                'archivo' => ['required', 'file', 'extensions:xlsx,csv', 'max:40960'],
                'periodo' => ['required', 'date_format:Y-m'],
            ],
            [
                'archivo.required' => 'Elige la descarga de Geolice (archivo .xlsx o .csv).',
                'archivo.file' => 'El archivo no se recibió completo; inténtalo de nuevo.',
                'archivo.extensions' => 'El archivo debe ser una descarga de Geolice en formato .xlsx o .csv.',
                'archivo.max' => 'El archivo supera el tamaño permitido (40 MB).',
                'periodo.required' => 'Indica el mes de pago.',
                'periodo.date_format' => 'El mes de pago no tiene un formato válido.',
            ]
        );

        $codigo = str_replace('-', '', $datos['periodo']);

        $existente = CourierPeriodo::query()
            ->where('codigo', $codigo)
            ->first();

        if ($existente?->estaCerrado()) {
            return back()
                ->withErrors([
                    'periodo' => "El período {$existente->nombre} está cerrado; no se puede cargar sobre él."
                ])
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
                ->withErrors([
                    'archivo' => 'Falló la importación, no se guardó nada: ' . $e->getMessage()
                ])
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
