<?php

namespace App\Http\Controllers;


use App\Models\Bsale;
use App\Services\BsaleGuiaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;




class BsaleGuiaImportacionController extends Controller
{


    private const COLUMNAS = [
        'FechaGuiaTransporte',
        'Troncal',
        'Posta',
        'DestinoCarga',
        'DireccionOrigen',
        'DireccionDestino',
        'Patente',
        'Chofer',
        'RutChofer',
        'Detalle',
        'Bultos',
        'PesoTotal',
    ];


    public function index(Request $request)
    {
        $historial = Bsale::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('ambiente', 'production')
            ->where('empresa_bsale_id', '101346')
            ->orderByDesc('id')
            ->paginate(15);

        $grupos = [];
        $filas = [];
        $resultados = [];

        foreach ($historial as $registro) {
            $grupo = $registro->datos_grupo;

            $grupo['identificador'] = $registro->identificador;
            $grupo['archivo_origen'] = $registro->archivo_origen;
            $grupo['comuna_destino'] = $registro->comuna_destino;
            $grupo['ciudad_destino'] = $registro->ciudad_destino;

            $grupos[] = $grupo;

            foreach ($grupo['filas'] as $fila) {
                $filas[] = $fila;
            }

            $resultados[$registro->identificador] = [
                'estado' => $registro->estado,
                'numero' => $registro->numero_guia,
                'pdf' => $registro->url_pdf,
                'vista' => $registro->url_vista,
                'mensaje' => $registro->mensaje_error,
            ];
        }

        return view('bsale.guias.importar', [
            'columnas' => self::COLUMNAS,
            'filas' => $filas,
            'grupos' => $grupos,
            'archivo' => $historial->total() > 0
                ? 'Historial de guías importadas'
                : null,
            'resultados' => $resultados,
            'historial' => $historial,
        ]);
    }


    public function previsualizar(Request $request)
    {
        $request->validate([
            'excel' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'excel.required' => 'Selecciona el archivo Excel.',
            'excel.mimes' => 'El archivo debe tener formato XLSX.',
            'excel.max' => 'El archivo no debe superar los 5 MB.',
        ]);

        $archivo = $request->file('excel');
        $libro = null;

        try {
            $lector = new Xlsx();
            $informacion = $lector->listWorksheetInfo(
                $archivo->getRealPath()
            );

            // Primera versión: un archivo con una sola hoja.
            if (count($informacion) !== 1) {
                $this->rechazar(
                    'Esta primera versión admite archivos con una sola hoja.'
                );
            }

            if ($informacion[0]['totalRows'] > 5001) {
                $this->rechazar(
                    'Esta previsualización admite hasta 5.000 filas de datos.'
                );
            }

            if ($informacion[0]['totalColumns'] !== count(self::COLUMNAS)) {
                $this->rechazar(
                    'El archivo debe contener las 12 columnas del formato GuiasBsale.'
                );
            }

            $libro = $lector->load($archivo->getRealPath());
            $hoja = $libro->getSheet(0);

            $encabezados = [];

            foreach (self::COLUMNAS as $indice => $columna) {
                $encabezados[] = trim(
                    (string) $hoja->getCell([$indice + 1, 1])->getValue()
                );
            }

            if ($encabezados !== self::COLUMNAS) {
                $this->rechazar(
                    'Los encabezados deben tener estos nombres y este orden: '
                    . implode(', ', self::COLUMNAS)
                );
            }

            $filas = [];

            for ($numero = 2; $numero <= $hoja->getHighestDataRow(); $numero++) {
                $datos = [];

                foreach (self::COLUMNAS as $indice => $columna) {
                    $celda = $hoja->getCell([$indice + 1, $numero]);

                    if ($celda->getDataType() === DataType::TYPE_FORMULA) {
                        $this->rechazar(
                            "La fila {$numero}, columna {$columna}, contiene "
                            . 'una fórmula. Exporta sus valores desde Access.'
                        );
                    }

                    $valor = $celda->getValue();

                    $datos[$columna] = is_string($valor)
                        ? trim($valor)
                        : $valor;
                }

                // Omitir únicamente filas completamente vacías.
                $tieneDatos = count(array_filter(
                    $datos,
                    fn ($valor) => $valor !== null && $valor !== ''
                )) > 0;

                if (! $tieneDatos) {
                    continue;
                }

                // Excel almacena sus fechas como números.
                $fecha = $datos['FechaGuiaTransporte'];

                if (is_int($fecha) || is_float($fecha)) {
                    $datos['FechaGuiaTransporte'] = Date::excelToDateTimeObject(
                        $fecha
                    )->format('Y-m-d');
                }

                $filas[] = [
                    'numero_excel' => $numero,
                    'datos' => $datos,
                ];
            }

            if ($filas === []) {
                $this->rechazar('El archivo no contiene filas de datos.');
            }








            $grupos = $this->agruparFilas($filas);

            $this->guardarGrupos(
                $request,
                $grupos,
                $archivo->getClientOriginalName()
            );

            return redirect()->route('bsale.guias.index');









        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            report($exception);

            $this->rechazar(
                'No se pudo leer el Excel. Comprueba que abre correctamente '
                . 'y que no está protegido con contraseña.'
            );



        } finally {
            if ($libro instanceof Spreadsheet) {
                $libro->disconnectWorksheets();
            }
        }



    }





    public function generar(Request $request, BsaleGuiaService $bsale, string $identificador) 
    {
        $registro = Bsale::query()
            ->where('identificador', $identificador)
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('ambiente', 'production')
            ->where('empresa_bsale_id', '101346')
            ->firstOrFail();

        // Volver a la página del historial donde está este registro.
        $anteriores = Bsale::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('ambiente', 'production')
            ->where('empresa_bsale_id', '101346')
            ->where('id', '>', $registro->id)
            ->count();

        $pagina = intdiv($anteriores, 15) + 1;

        $volver = route('bsale.guias.index', ['page' => $pagina])
            . '#grupo-' . $identificador;

        $estadosPermitidos = [
            Bsale::ESTADO_PENDIENTE,
            Bsale::ESTADO_ERROR,
        ];

        if (! in_array($registro->estado, $estadosPermitidos, true)) {
            return redirect()->to($volver);
        }

        $destino = $request->validate([
            'municipality' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
        ], [
            'municipality.required' => 'Indica la comuna de destino.',
            'city.required' => 'Indica la ciudad de destino.',
        ]);

        $grupo = $registro->datos_grupo;
        $cabecera = $grupo['cabecera'];

        $lineas = array_map(
            fn ($fila) => $fila['datos'],
            $grupo['filas']
        );

        $reglasCabecera = [];

        foreach (array_slice(self::COLUMNAS, 0, 9) as $campo) {
            $reglasCabecera['cabecera.' . $campo] = [
                'required',
                'string',
            ];
        }

        $reglasCabecera['cabecera.FechaGuiaTransporte'] = [
            'required',
            'date_format:Y-m-d',
        ];

        Validator::make(
            [
                'cabecera' => $cabecera,
                'lineas' => $lineas,
            ],
            array_merge($reglasCabecera, [
                'lineas' => ['required', 'array', 'min:1'],
                'lineas.*.Detalle' => ['required', 'string'],
                'lineas.*.Bultos' => ['required', 'integer', 'min:1'],
                'lineas.*.PesoTotal' => ['required', 'numeric', 'min:0'],
            ]),
            [
                'required' => 'Falta el dato :attribute.',
                'string' => 'El dato :attribute debe contener texto.',
                'date_format' => 'La fecha debe tener formato AAAA-MM-DD.',
                'integer' => 'Los bultos deben ser números enteros.',
                'numeric' => 'El dato :attribute debe ser numérico.',
                'min' => 'El dato :attribute debe ser al menos :min.',
            ]
        )->validate();

        $fecha = new \DateTimeImmutable(
            $cabecera['FechaGuiaTransporte'],
            new \DateTimeZone('UTC')
        );

        $totalBultos = array_sum(array_column($lineas, 'Bultos'));
        $totalKilos = array_sum(array_column($lineas, 'PesoTotal'));

        $transporte = implode("\n", [
            $cabecera['DestinoCarga'],
            'Troncal: ' . $cabecera['Troncal'],
            'Posta: ' . $cabecera['Posta'],
            'Patente: ' . $cabecera['Patente'],
            'Chofer: ' . $cabecera['Chofer'],
            'RUT chofer: ' . $cabecera['RutChofer'],
            'Total bultos: ' . $totalBultos,
            'Total kilos: ' . $totalKilos,
        ]);

        $detalles = array_map(
            fn ($linea) => [
                'comment' => $linea['Detalle'],
                'quantity' => (int) $linea['Bultos'],
            ],
            $lineas
        );

        // Mantener los clientes juntos y el transporte al final.
        $ultimoIndice = array_key_last($detalles);
        $detalles[$ultimoIndice]['comment'] .= "\n\n" . $transporte;

        $datos = [
            'documentTypeId' => 7,
            'declareSii' => 0,
            'sendEmail' => 0,

            'emissionDate' => $fecha->getTimestamp(),

            'client' => [
                'code' => '77346078-7',
                'company' => '4 NORTES LOGISTICA SPA',
                'activity' => 'Servicios De Logistica Y Distribucion',
                'address' => 'Galvarino 8481, Bodega 17',
                'municipality' => 'Quilicura',
                'city' => 'Santiago',
            ],

            'address' => $cabecera['DireccionDestino'],
            'municipality' => $destino['municipality'],
            'city' => $destino['city'],
            'details' => $detalles,
        ];

        // Revisar nuevamente el estado bajo bloqueo de BD.
        $puedeEnviar = DB::transaction(function () use (
            $registro,
            $estadosPermitidos,
            $destino,
            $datos
        ) {
            $actual = Bsale::query()
                ->whereKey($registro->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($actual->estado, $estadosPermitidos, true)) {
                return false;
            }

            $actual->fill([
                'comuna_destino' => $destino['municipality'],
                'ciudad_destino' => $destino['city'],
                'document_type_id' => 7,
                'declare_sii' => false,
                'estado' => Bsale::ESTADO_ENVIANDO,
                'payload_enviado' => $datos,
                'mensaje_error' => 'Envío iniciado. Si no se completa, '
                    . 'comprueba el resultado en Bsale antes de reenviar.',
                'estado_http' => null,
                'respuesta_bsale' => null,
                'envio_iniciado_at' => now(),
                'respuesta_recibida_at' => null,
            ]);

            $actual->save();

            return true;
        });

        if (! $puedeEnviar) {
            return redirect()->to($volver);
        }

        // La llamada externa queda fuera de la transacción.
        try {
            $resultado = $bsale->generar($datos);
        } catch (\Throwable $exception) {
            $resultado = [
                'estado' => Bsale::ESTADO_INCIERTA,
                'mensaje' => 'La generación se interrumpió. Comprueba '
                    . 'en Bsale si la guía fue creada antes de reenviarla.',
            ];
        }

        try {
            DB::transaction(function () use ($registro, $resultado) {
                $actual = Bsale::query()
                    ->whereKey($registro->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($actual->estado !== Bsale::ESTADO_ENVIANDO) {
                    return;
                }

                $actual->fill($this->camposResultado($resultado));
                $actual->save();
            });
        } catch (\Throwable $exception) {
            report($exception);

            // El registro conserva "enviando", evitando otro POST.
            return redirect()->to($volver)->withErrors([
                'generacion' => 'El envío fue realizado, pero no pudimos '
                    . 'guardar su resultado. Comprueba la guía en Bsale; '
                    . 'no vuelvas a generarla.',
            ]);
        }

        return redirect()->to($volver);
    }






    private function agruparFilas(array $filas): array
    {
        $camposCabecera = array_slice(self::COLUMNAS, 0, 9);
        $grupos = [];

        foreach ($filas as $fila) {
            $cabecera = [];

            foreach ($camposCabecera as $campo) {
                $cabecera[$campo] = $fila['datos'][$campo];
            }

            $clave = json_encode(
                $cabecera,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );

            if (! isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'cabecera' => $cabecera,
                    'filas' => [],
                ];
            }

            $grupos[$clave]['filas'][] = $fila;
        }

        foreach ($grupos as &$grupo) {
            foreach (['Bultos', 'PesoTotal'] as $campo) {
                $valores = array_map(
                    fn ($fila) => $fila['datos'][$campo],
                    $grupo['filas']
                );

                // No presentar un total engañoso si hay valores vacíos o texto.
                $todosNumericos = count(
                    array_filter($valores, 'is_numeric')
                ) === count($valores);

                $grupo['totales'][$campo] = $todosNumericos
                    ? array_sum($valores)
                    : null;
            }
        }

        unset($grupo);

        return array_values($grupos);
    }



    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages([
            'excel' => $mensaje,
        ]);
    }


    private function guardarGrupos(Request $request, array $grupos, string $archivoOrigen): void 
    {
        $usuario = $request->user();
        $resultadosAnteriores = $request->session()->get(
            'bsale_resultados',
            []
        );

        DB::transaction(function () use (
            $usuario,
            $grupos,
            $archivoOrigen,
            $resultadosAnteriores
        ) {
            // Serializar las importaciones del mismo usuario,
            // incluso cuando procedan de sesiones distintas.
            $usuario->newQuery()
                ->whereKey($usuario->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($grupos as $grupo) {
                $contenido = [
                    'cabecera' => $grupo['cabecera'],
                    'detalles' => array_map(
                        fn ($fila) => $fila['datos'],
                        $grupo['filas']
                    ),
                ];

                // Conservamos el mismo cálculo utilizado anteriormente.
                $huella = hash(
                    'sha256',
                    json_encode(
                        $contenido,
                        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                    )
                );

                $existente = Bsale::query()
                    ->where('user_id', $usuario->getAuthIdentifier())
                    ->where('ambiente', 'production')
                    ->where('empresa_bsale_id', '101346')
                    ->where('huella_datos', $huella)
                    ->orderBy('id')
                    ->first();

                if ($existente) {
                    // No sobrescribir datos ni resultados históricos.
                    continue;
                }

                $registro = new Bsale([
                    'huella_datos' => $huella,
                    'user_id' => $usuario->getAuthIdentifier(),
                    'archivo_origen' => $archivoOrigen,
                    'datos_grupo' => $grupo,
                    'ambiente' => 'production',
                    'empresa_bsale_id' => '101346',
                    'document_type_id' => 7,
                    'declare_sii' => false,
                    'estado' => Bsale::ESTADO_PENDIENTE,
                ]);

                $anterior = $resultadosAnteriores[$huella] ?? null;

                if (
                    is_array($anterior)
                    && in_array(
                        $anterior['estado'] ?? null,
                        [
                            Bsale::ESTADO_ENVIANDO,
                            Bsale::ESTADO_GENERADA,
                            Bsale::ESTADO_ERROR,
                            Bsale::ESTADO_INCIERTA,
                        ],
                        true
                    )
                ) {
                    $registro->fill(
                        $this->camposResultado($anterior, false)
                    );
                }

                $registro->save();
            }
        });
    }


    private function camposResultado( array $resultado, bool $respuestaActual = true): array 
    {
        $estado = $resultado['estado'] ?? Bsale::ESTADO_INCIERTA;

        $respuesta = $resultado['respuesta'] ?? null;

        if ($respuesta !== null && ! is_array($respuesta)) {
            $respuesta = ['body' => (string) $respuesta];
        }

        return [
            'estado' => $estado,
            'bsale_shipping_id' => $resultado['shipping_id'] ?? null,
            'bsale_document_id' => $resultado['document_id'] ?? null,
            'numero_guia' => $resultado['numero'] ?? null,
            'url_pdf' => $resultado['pdf'] ?? null,
            'url_vista' => $resultado['vista'] ?? null,
            'estado_http' => $resultado['estado_http'] ?? null,
            'respuesta_bsale' => $respuesta,
            'mensaje_error' => $resultado['mensaje'] ?? null,

            'respuesta_recibida_at' => (
                $respuestaActual
                && isset($resultado['estado_http'])
            ) ? now() : null,

            'generada_at' => (
                $respuestaActual
                && $estado === Bsale::ESTADO_GENERADA
            ) ? now() : null,
        ];
    }



}