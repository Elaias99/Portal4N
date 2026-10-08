<?php

namespace App\Services\Courier;

use App\Imports\Courier\FiltroColumnas;
use App\Imports\Courier\GeoliceBultosImport;
use App\Imports\Courier\PesajesImport;
use App\Imports\Courier\PesoRealImport;
use App\Models\CourierAcuerdo;
use App\Models\CourierBulto;
use App\Models\CourierCierre;
use App\Models\CourierCoberturaComuna;
use App\Models\CourierControl;
use App\Models\CourierEstadoEntrega;
use App\Models\CourierImportacion;
use App\Models\CourierPagoProceso;
use App\Models\CourierPeriodo;
use App\Models\CourierPesaje;
use App\Models\CourierProveedor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as TipoExcel;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/*
 * Proceso mensual de pago Courier: períodos, cargas de datos y
 * diagnóstico de lo cargado contra los catálogos.
 *
 * Es la contraparte para pantalla de los comandos courier:importar-*;
 * ambos usan las mismas clases de importación.
 */
class CourierPagoService
{
    /*
     * Carpeta donde queda el archivo mientras el usuario mira la
     * revisión y decide si lo confirma. Es temporal: se borra al
     * confirmar, al descartar, o cuando pasa un día.
     */
    private const CARPETA_REVISIONES = 'courier/revisiones';

    /* Pagos extra que se suben en el paso Pagos extra: clave del formulario → nombre. */
    public const PAGOS_EXTRA = [
        'acuerdos' => 'Acuerdos',
        'ruta-cv' => CourierPagoProceso::RUTA_CV,
        'servicios' => CourierPagoProceso::SERVICIOS,
        'visitas' => CourierPagoProceso::VISITAS,
        'especiales' => CourierPagoProceso::ESPECIALES,
    ];

    /*
     * Lo cargado de cada pago extra en el período, más Apoyo Alza, que no
     * se sube: se arma solo con sus reglas.
     *
     * @return array<string, array{nombre: string, filas: int, total: int, archivo: ?string, cargado: ?string}>
     */
    public function pagosExtra(CourierPeriodo $periodo): array
    {
        $resumen = function ($consulta) {
            $fila = (clone $consulta)
                ->selectRaw('COUNT(*) filas, COALESCE(SUM(total), 0) total, MAX(archivo_origen) archivo, MAX(updated_at) cargado')
                ->first();

            return [
                'filas' => (int) $fila->filas,
                'total' => (int) $fila->total,
                'archivo' => $fila->archivo,
                'cargado' => $fila->cargado,
            ];
        };

        $extras = [];

        foreach (self::PAGOS_EXTRA as $clave => $nombre) {
            $consulta = $clave === 'acuerdos'
                ? CourierAcuerdo::query()->delPeriodo($periodo->id)
                : CourierPagoProceso::query()->delPeriodo($periodo->id)->where('proceso', $nombre);

            $extras[$clave] = ['nombre' => $nombre] + $resumen($consulta);
        }

        $extras['apoyo-alza'] = ['nombre' => CourierPagoProceso::APOYO_ALZA] + $resumen(
            CourierPagoProceso::query()->delPeriodo($periodo->id)->where('proceso', CourierPagoProceso::APOYO_ALZA)
        );

        return $extras;
    }

    public function periodos(): Collection
    {
        return CourierPeriodo::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();
    }

    /*
     * Período que muestra la pantalla: el pedido en la URL si existe;
     * si no, el más reciente.
     */
    public function periodoActual(?string $codigo, Collection $periodos): ?CourierPeriodo
    {
        if ($codigo !== null && $codigo !== '') {
            $elegido = $periodos->firstWhere('codigo', $codigo);

            if ($elegido instanceof CourierPeriodo) {
                return $elegido;
            }
        }

        return $periodos->first();
    }

    public function importaciones(CourierPeriodo $periodo): Collection
    {
        return CourierImportacion::query()
            ->with('usuario:id,name')
            ->where('courier_periodo_id', $periodo->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /*
     * Carga una descarga de Geolice en courier_bultos y deja registro en
     * courier_importaciones. Todo o nada: si algo falla no queda ni el
     * período nuevo ni bultos a medias.
     */
    public function importarGeolice(UploadedFile $archivo, string $codigoPeriodo, ?int $usuarioId): CourierImportacion
    {
        return $this->guardarBultos(
            $archivo->getRealPath(),
            $archivo->getClientOriginalName(),
            strtolower($archivo->getClientOriginalExtension()),
            $codigoPeriodo,
            $usuarioId
        );
    }

    /*
     * Confirma una revisión: importa el archivo que quedó guardado
     * cuando el usuario lo revisó, sin pedirle que lo suba otra vez.
     */
    public function confirmarGeolice(string $token, ?int $usuarioId): CourierImportacion
    {
        $revision = $this->leerRevision($token);

        if (isset($revision['origen_captura'])
            && (int) $revision['origen_captura']['user_id'] !== $usuarioId) {
            throw new \InvalidArgumentException('La revisión de esta captura no pertenece a tu cuenta.');
        }

        $importacion = $this->guardarBultos(
            Storage::path($revision['ruta']),
            $revision['archivo'],
            $revision['extension'],
            $revision['periodo'],
            $usuarioId,
            $revision['origen_captura'] ?? null
        );

        $this->descartarRevision($token);

        return $importacion;
    }

    /*
     * Lee el archivo y guarda los bultos. Es el único lugar que escribe:
     * lo usan la confirmación desde pantalla y el comando de terminal.
     * Todo o nada: si algo falla no queda ni el período nuevo ni bultos
     * a medias.
     */
    private function guardarBultos(
        string $ruta,
        string $nombre,
        string $extension,
        string $codigoPeriodo,
        ?int $usuarioId,
        ?array $origenCaptura = null
    ): CourierImportacion {
        // La descarga trae decenas de miles de filas.
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $inicio = microtime(true);

        return DB::transaction(function () use ($ruta, $nombre, $extension, $codigoPeriodo, $usuarioId, $inicio, $origenCaptura) {
            $periodo = CourierPeriodo::firstOrCreate(
                ['codigo' => $codigoPeriodo],
                [
                    'anio' => (int) substr($codigoPeriodo, 0, 4),
                    'mes' => (int) substr($codigoPeriodo, 4, 2),
                    'estado' => 'abierto',
                ]
            );

            $import = $this->leerArchivoGeolice($ruta, $extension, $periodo, $nombre);

            return CourierImportacion::create([
                'courier_periodo_id' => $periodo->id,
                'tipo' => CourierImportacion::TIPO_GEOLICE,
                'archivo' => $nombre,
                'user_id' => $usuarioId,
                'filas' => $import->resumen['filas'],
                'nuevos' => $import->resumen['nuevos'],
                'actualizados' => $import->resumen['actualizados'],
                'duracion_seg' => (int) round(microtime(true) - $inicio),
                'resumen' => [
                    'resumen' => $import->resumen,
                    'estados' => $this->ordenar($import->estados),
                    'estados_desconocidos' => $import->estadosDesconocidos,
                    'comunas_fuera_de_catalogo' => $this->ordenar($import->comunasFueraDeCatalogo),
                    'sin_configuracion' => $this->ordenar($import->sinConfiguracion),
                    ...($origenCaptura === null ? [] : ['origen_captura' => $origenCaptura]),
                ],
            ]);
        });
    }

    /*
     * Carga uno o más CSV de pesajes de bodega. Cada archivo deja su
     * propio registro en courier_importaciones; la fecha del pesaje sale
     * del nombre del archivo. Todo o nada para el lote completo.
     *
     * @param  UploadedFile[]  $archivos
     */
    public function importarPesajes(array $archivos, string $codigoPeriodo, ?int $usuarioId): Collection
    {
        set_time_limit(0);

        return DB::transaction(function () use ($archivos, $codigoPeriodo, $usuarioId) {
            $periodo = CourierPeriodo::firstOrCreate(
                ['codigo' => $codigoPeriodo],
                [
                    'anio' => (int) substr($codigoPeriodo, 0, 4),
                    'mes' => (int) substr($codigoPeriodo, 4, 2),
                    'estado' => 'abierto',
                ]
            );

            $registros = collect();

            foreach ($archivos as $archivo) {
                $nombre = $archivo->getClientOriginalName();
                $fecha = self::fechaDesdeNombre($nombre);

                if ($fecha === null) {
                    throw new \InvalidArgumentException(
                        "No se pudo leer la fecha del pesaje en el nombre \"{$nombre}\". El archivo debe llamarse como lo entrega bodega (Proceso del dia dd-mm-aaaa.csv)."
                    );
                }

                $inicio = microtime(true);

                $import = new PesajesImport($periodo, $fecha, $nombre);
                $import->importar($archivo->getRealPath());

                $registros->push(CourierImportacion::create([
                    'courier_periodo_id' => $periodo->id,
                    'tipo' => CourierImportacion::TIPO_PESAJES,
                    'archivo' => $nombre,
                    'user_id' => $usuarioId,
                    'filas' => $import->resumen['filas'],
                    'nuevos' => $import->resumen['nuevos'],
                    'actualizados' => $import->resumen['actualizados'],
                    'duracion_seg' => (int) round(microtime(true) - $inicio),
                    'resumen' => [
                        'resumen' => $import->resumen,
                        'fecha_pesaje' => $fecha,
                        'codigos_invalidos' => $import->codigosInvalidos,
                        'sin_bulto' => array_slice($import->sinBulto, 0, 200, true),
                    ],
                ]));
            }

            return $registros;
        });
    }

    /*
     * Encabezado de la plantilla de pesos (primera hoja del Excel). Sólo
     * se exigen las cuatro primeras columnas; Comerciante y Servicio
     * pueden venir y no se usan.
     */
    public const COLUMNAS_PLANTILLA_PESOS = ['Codigo_S+Bulto', 'Notas', 'Cod_seguimiento', 'Fecha de maestro'];

    /*
     * Carga la plantilla de pesos (Excel) en courier_pesajes del período,
     * con el mismo lector de la hoja PesoReal. Deja registro en
     * courier_importaciones. Todo o nada. No recalcula: eso lo decide
     * la persona con el botón Calcular.
     */
    public function importarPlantillaPesos(UploadedFile $archivo, CourierPeriodo $periodo, ?int $usuarioId): CourierImportacion
    {
        set_time_limit(0);

        $filas = $this->leerPlantillaPesos($archivo->getRealPath());
        $nombre = $archivo->getClientOriginalName();

        return DB::transaction(function () use ($filas, $nombre, $periodo, $usuarioId) {
            $inicio = microtime(true);

            $import = new PesoRealImport($periodo, $nombre);
            $import->collection($filas);

            /* Cuántos bultos del período quedaron con un peso de balanza que sirve. */
            $conPeso = CourierBulto::query()
                ->delPeriodo($periodo->id)
                ->whereExists(fn ($q) => $q->selectRaw('1')
                    ->from('courier_pesajes as p')
                    ->where('p.courier_periodo_id', $periodo->id)
                    ->whereColumn('p.seguimiento', 'courier_bultos.seguimiento')
                    ->where('p.kilos', '>', 0))
                ->count();

            return CourierImportacion::create([
                'courier_periodo_id' => $periodo->id,
                'tipo' => CourierImportacion::TIPO_PESAJES,
                'archivo' => $nombre,
                'user_id' => $usuarioId,
                'filas' => $import->resumen['filas'],
                'nuevos' => $import->resumen['nuevos'],
                'actualizados' => $import->resumen['actualizados'],
                'duracion_seg' => (int) round(microtime(true) - $inicio),
                'resumen' => [
                    'resumen' => $import->resumen,
                    'formato' => 'plantilla_pesos',
                    'bultos_con_peso' => $conPeso,
                ],
            ]);
        });
    }

    /*
     * Primera hoja de la plantilla, sólo sus cuatro primeras columnas.
     * Si el encabezado no es el de la plantilla, no se carga nada.
     */
    private function leerPlantillaPesos(string $ruta): Collection
    {
        $lector = IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $lector->setReadFilter(new FiltroColumnas(count(self::COLUMNAS_PLANTILLA_PESOS)));

        $libro = $lector->load($ruta);
        $filas = $libro->getSheet(0)->toArray(null, false, false, false);
        $libro->disconnectWorksheets();
        unset($libro, $lector);

        $normalizar = fn ($v) => mb_strtolower(trim((string) $v));
        $encabezado = array_map($normalizar, array_slice($filas[0] ?? [], 0, count(self::COLUMNAS_PLANTILLA_PESOS)));

        if ($encabezado !== array_map($normalizar, self::COLUMNAS_PLANTILLA_PESOS)) {
            throw new \InvalidArgumentException(
                'La primera hoja no tiene las columnas de la plantilla: ' . implode(' · ', self::COLUMNAS_PLANTILLA_PESOS) . '.'
            );
        }

        return collect($filas);
    }

    /*
     * "Proceso del dia 04-09-2026.csv" → "2026-09-04". Acepta también
     * aaaa-mm-dd. Null si el nombre no trae fecha o no es válida.
     */
    public static function fechaDesdeNombre(string $nombre): ?string
    {
        if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', $nombre, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $nombre, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return null;
    }

    /*
     * Diagnóstico vivo del período: se recalcula desde courier_bultos
     * contra los catálogos de HOY. Si alguien agrega una comuna o una
     * configuración, la alerta desaparece sola en la próxima visita.
     */
    public function alertas(CourierPeriodo $periodo): array
    {
        $bultos = CourierBulto::query()->delPeriodo($periodo->id);

        $total = (clone $bultos)->count();

        $pesajes = [
            'registros' => CourierPesaje::query()->delPeriodo($periodo->id)->count(),
            'dias' => CourierPesaje::query()->delPeriodo($periodo->id)->distinct()->count('fecha_pesaje'),
        ];

        if ($total === 0) {
            return [
                'total' => 0,
                'sin_comuna' => 0,
                'sin_peso_declarado' => 0,
                'con_pesaje' => 0,
                'sin_pesaje' => 0,
                'pesajes' => $pesajes,
                'estados' => [],
                'estados_desconocidos' => [],
                'comunas_fuera_de_catalogo' => [],
                'comunas_fuera_bultos' => 0,
                'comunas_fuera_ejemplos' => [],
                'sin_configuracion' => [],
                'sin_configuracion_bultos' => 0,
            ];
        }

        /* Bultos del período que tienen al menos un pesaje de bodega. */
        $conPesaje = (clone $bultos)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('courier_pesajes')
                    ->whereColumn('courier_pesajes.seguimiento', 'courier_bultos.seguimiento');
            })
            ->count();

        $estadosCatalogo = CourierEstadoEntrega::pluck('considerar', 'estado')->all();

        $estados = (clone $bultos)
            ->select('estado_entrega', DB::raw('COUNT(*) AS n'))
            ->groupBy('estado_entrega')
            ->orderByDesc('n')
            ->get()
            ->map(fn ($fila) => [
                'estado' => $fila->estado_entrega,
                'bultos' => (int) $fila->n,
                'en_catalogo' => array_key_exists($fila->estado_entrega, $estadosCatalogo),
                'considerar' => $estadosCatalogo[$fila->estado_entrega] ?? null,
            ])
            ->all();

        $estadosDesconocidos = array_values(array_filter($estados, fn ($e) => ! $e['en_catalogo']));

        $cobertura = CourierCoberturaComuna::query()
            ->with('agente:id,nombre')
            ->get(['localidad_clave', 'courier_agente_id'])
            ->mapWithKeys(fn ($c) => [$c->localidad_clave => $c->agente?->nombre ?? ''])
            ->all();

        /*
         * Se agrupa en binario para que "CONCÓN" y "Concón" no caigan en
         * el mismo grupo: la colación por defecto ignoraría mayúsculas y
         * tildes y escondería justamente las variantes que buscamos.
         */
        $grupos = (clone $bultos)
            ->whereNotNull('comuna_destino')
            ->selectRaw('comuna_destino COLLATE utf8mb4_bin AS comuna, COUNT(*) AS n')
            ->groupBy('comuna')
            ->get();

        $comunasFuera = [];

        foreach ($grupos as $grupo) {
            if (! isset($cobertura[CourierCoberturaComuna::clave($grupo->comuna)])) {
                $comunasFuera[$grupo->comuna] = ($comunasFuera[$grupo->comuna] ?? 0) + (int) $grupo->n;
            }
        }

        /* La llave es la del cálculo, no el catálogo viejo: ver bultosSinLlave(). */
        $sinConfiguracion = array_map(
            fn (array $fila) => $fila['bultos'],
            $this->bultosSinLlave($periodo, $cobertura, [])
        );

        return [
            'total' => $total,
            'sin_comuna' => (clone $bultos)->whereNull('comuna_destino')->count(),
            'sin_peso_declarado' => (clone $bultos)->whereNull('peso_declarado')->count(),
            'con_pesaje' => $conPesaje,
            'sin_pesaje' => $total - $conPesaje,
            'pesajes' => $pesajes,
            'estados' => $estados,
            'estados_desconocidos' => $estadosDesconocidos,
            'comunas_fuera_de_catalogo' => $this->ordenar($comunasFuera),
            'comunas_fuera_bultos' => array_sum($comunasFuera),
            'comunas_fuera_ejemplos' => $this->ejemplosPorComuna($periodo, array_keys($comunasFuera)),
            'sin_configuracion' => $this->ordenar($sinConfiguracion),
            'sin_configuracion_bultos' => array_sum($sinConfiguracion),
        ];
    }






    /*
     * Revisión previa: lee el archivo, lo diagnostica contra los
     * catálogos y NO escribe nada en la base. El archivo queda guardado
     * en disco para que la confirmación no obligue a subirlo otra vez;
     * el token que se devuelve es la forma de volver a él.
     */
    public function revisarGeolice(
        UploadedFile $archivo,
        string $codigoPeriodo,
        ?array $origenCaptura = null
    ): array {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $nombre = $archivo->getClientOriginalName();
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new \InvalidArgumentException("Formato de archivo no soportado: {$extension}");
        }

        $this->limpiarRevisionesViejas();

        $token = bin2hex(random_bytes(16));
        $ruta = self::CARPETA_REVISIONES . "/{$token}.{$extension}";

        Storage::putFileAs(self::CARPETA_REVISIONES, $archivo, "{$token}.{$extension}");

        /*
         * El período puede no existir todavía: en ese caso se trabaja
         * con una instancia sin guardar, para no crear un período por
         * el solo hecho de mirar un archivo. Sin id, cualquier bulto
         * que ya exista cuenta como de un período anterior, que es
         * justamente lo que corresponde.
         */
        $periodo = CourierPeriodo::query()->where('codigo', $codigoPeriodo)->first()
            ?? new CourierPeriodo([
                'codigo' => $codigoPeriodo,
                'anio' => (int) substr($codigoPeriodo, 0, 4),
                'mes' => (int) substr($codigoPeriodo, 4, 2),
                'estado' => 'abierto',
            ]);

        try {
            $import = $this->leerArchivoGeolice(
                Storage::path($ruta),
                $extension,
                $periodo,
                $nombre,
                soloAnalizar: true
            );
        } catch (\Throwable $e) {
            Storage::delete($ruta);

            throw $e;
        }

        Storage::put(
            self::CARPETA_REVISIONES . "/{$token}.json",
            json_encode([
                'archivo' => $nombre,
                'extension' => $extension,
                'periodo' => $codigoPeriodo,
                'ruta' => $ruta,
                ...($origenCaptura === null ? [] : ['origen_captura' => $origenCaptura]),
            ], JSON_UNESCAPED_UNICODE)
        );

        return [
            'token' => $token,
            'archivo' => $nombre,
            'periodo' => $codigoPeriodo,
            'resumen' => $import->resumen,
            'estados' => $this->ordenar($import->estados),
            'estados_desconocidos' => $import->estadosDesconocidos,
            'comunas_fuera_de_catalogo' => $this->ordenar(
                $import->comunasFueraDeCatalogo
            ),
            'comunas_fuera_ejemplos' => $import->comunasFueraEjemplos,
            'sin_configuracion' => $this->ordenar(
                $import->sinConfiguracion
            ),
            ...($origenCaptura === null ? [] : ['origen_captura' => $origenCaptura]),
        ];
    }

    /**
     * Revisa un archivo que la captura ya descargó. No guarda bultos ni calcula pagos.
     *
     * @param array{capture_id: string, user_id: int, from: string, to: string, payment_period: string} $origenCaptura
     * @return array<string, mixed>
     */
    public function revisarCapturaGeolice(
        string $ruta,
        string $nombre,
        string $codigoPeriodo,
        array $origenCaptura
    ): array {
        return $this->revisarGeolice(
            new UploadedFile($ruta, $nombre, null, null, true),
            $codigoPeriodo,
            $origenCaptura
        );
    }

    /*
     * Lectura del archivo, común a la revisión y a la importación.
     *
     * El CSV se lee directamente con fgetcsv en vez de pasar por
     * Laravel Excel: con lectura por lotes, Laravel Excel recorre el
     * archivo entero una vez por lote, y en una descarga de decenas de
     * miles de filas eso se nota. El xlsx sí lo necesita.
     */
    private function leerArchivoGeolice(
        string $ruta,
        string $extension,
        CourierPeriodo $periodo,
        string $nombre,
        bool $soloAnalizar = false
    ): GeoliceBultosImport {
        $delimitador = $extension === 'csv'
            ? $this->detectarDelimitadorCsv($ruta)
            : ',';

        $import = new GeoliceBultosImport(
            $periodo,
            $nombre,
            $delimitador,
            $soloAnalizar
        );

        if ($extension === 'csv') {
            $this->leerCsvPorLotes($import, $ruta, $delimitador);

            return $import;
        }

        Excel::import($import, $ruta, null, TipoExcel::XLSX);

        return $import;
    }

    /*
     * Recorre el CSV una sola vez y entrega los lotes al importador,
     * igual que haría Laravel Excel pero sin releer el archivo.
     */
    private function leerCsvPorLotes(
        GeoliceBultosImport $import,
        string $ruta,
        string $delimitador
    ): void {
        $archivo = fopen($ruta, 'r');

        if ($archivo === false) {
            throw new \RuntimeException('No se pudo abrir el archivo de Geolice.');
        }

        try {
            // Encabezado.
            fgetcsv($archivo, 0, $delimitador, '"', '');

            $lote = [];
            $porLote = $import->chunkSize();

            while (($campos = fgetcsv($archivo, 0, $delimitador, '"', '')) !== false) {
                if ($campos === [null]) {
                    continue; // línea en blanco
                }

                $lote[] = collect($campos);

                if (count($lote) >= $porLote) {
                    $import->collection(collect($lote));
                    $lote = [];
                }
            }

            if ($lote !== []) {
                $import->collection(collect($lote));
            }
        } finally {
            fclose($archivo);
        }
    }

    /*
     * Devuelve los datos de una revisión guardada. El token se valida
     * con formato estricto: es parte de una ruta de archivo.
     */
    private function leerRevision(string $token): array
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new \InvalidArgumentException('La revisión no es válida.');
        }

        $meta = self::CARPETA_REVISIONES . "/{$token}.json";

        if (! Storage::exists($meta)) {
            throw new \RuntimeException(
                'La revisión ya no está disponible; vuelve a cargar el archivo.'
            );
        }

        $datos = json_decode(Storage::get($meta), true);

        if (! is_array($datos) || ! Storage::exists($datos['ruta'] ?? '')) {
            throw new \RuntimeException(
                'La revisión ya no está disponible; vuelve a cargar el archivo.'
            );
        }

        return $datos;
    }

    public function descartarRevision(?string $token): void
    {
        if ($token === null || ! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return;
        }

        foreach (Storage::files(self::CARPETA_REVISIONES) as $archivo) {
            if (str_starts_with(basename($archivo), $token . '.')) {
                Storage::delete($archivo);
            }
        }
    }

    /*
     * Un archivo revisado y nunca confirmado no debe quedar ocupando
     * disco para siempre.
     */
    private function limpiarRevisionesViejas(int $horas = 24): void
    {
        $limite = now()->subHours($horas)->getTimestamp();

        foreach (Storage::files(self::CARPETA_REVISIONES) as $archivo) {
            if (Storage::lastModified($archivo) < $limite) {
                Storage::delete($archivo);
            }
        }
    }








    /*
     * Lo que hoy se puede pagar del período: sólo los bultos que el
     * cálculo dejó en PAGAR. El IVA se agrega por proveedor, y sólo a
     * los que emiten Factura, igual que la hoja Banco de la planilla.
     *
     * Devuelve null si el período todavía no se ha calculado.
     */
    public function resumenPago(CourierPeriodo $periodo): ?array
    {
        $calculados = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->whereNotNull('calculado_at')
            ->count();

        if ($calculados === 0) {
            return null;
        }

        $filas = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->where('estado_pago', 'PAGAR')
            ->selectRaw('zona, tipo_pago, courier_proveedor_id, COUNT(*) AS bultos, SUM(valor) AS monto')
            ->groupBy('zona', 'tipo_pago', 'courier_proveedor_id')
            ->get();

        /*
         * Acuerdos, Ruta CV, Servicios, Visitas, Especiales y Apoyo Alza
         * no son bultos: entran al mismo resumen como una fila más por
         * zona, tipo y proveedor, con cero bultos.
         */
        $filas = $filas
            ->concat(
                CourierAcuerdo::query()
                    ->delPeriodo($periodo->id)
                    ->where('total', '>', 0)
                    ->selectRaw('zona, ? AS tipo_pago, courier_proveedor_id, 0 AS bultos, SUM(total) AS monto', [CourierAcuerdo::TIPO_PAGO])
                    ->groupBy('zona', 'courier_proveedor_id')
                    ->get()
            )
            ->concat(
                CourierPagoProceso::query()
                    ->delPeriodo($periodo->id)
                    ->where('total', '>', 0)
                    ->selectRaw('zona, proceso AS tipo_pago, courier_proveedor_id, 0 AS bultos, SUM(total) AS monto')
                    ->groupBy('zona', 'proceso', 'courier_proveedor_id')
                    ->get()
            );

        $proveedores = CourierProveedor::query()
            ->whereIn('id', $filas->pluck('courier_proveedor_id')->filter()->unique())
            ->get(['id', 'razon_social', 'rut', 'tipo_documento'])
            ->keyBy('id');

        $porZona = [];
        $porProveedor = [];
        $porTipo = [];
        $bultos = 0;
        $neto = 0;

        foreach ($filas as $fila) {
            $monto = (int) $fila->monto;
            $cantidad = (int) $fila->bultos;

            $bultos += $cantidad;
            $neto += $monto;

            $zona = $fila->zona ?: 'Sin zona';
            $tipo = $fila->tipo_pago ?: 'Variables';

            $porZona[$zona]['zona'] = $zona;
            $porZona[$zona]['bultos'] = ($porZona[$zona]['bultos'] ?? 0) + $cantidad;
            $porZona[$zona]['neto'] = ($porZona[$zona]['neto'] ?? 0) + $monto;

            $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + $monto;

            $proveedor = $fila->courier_proveedor_id
                ? $proveedores->get($fila->courier_proveedor_id)
                : null;

            /*
             * Sin proveedor resuelto el bulto igual tiene valor, pero no
             * se sabe a quién pagarle. Se agrupa aparte para que quede
             * a la vista y no se mezcle con los pagos reales.
             */
            $clave = $proveedor?->razon_social ?? '__sin_proveedor__';

            $porProveedor[$clave]['razon_social'] = $proveedor?->razon_social;
            $porProveedor[$clave]['rut'] = $proveedor?->rut;
            $porProveedor[$clave]['tipo_documento'] = $proveedor?->tipo_documento;
            $porProveedor[$clave]['zona'] = $porProveedor[$clave]['zona'] ?? $zona;
            $porProveedor[$clave]['bultos'] = ($porProveedor[$clave]['bultos'] ?? 0) + $cantidad;
            $porProveedor[$clave]['neto'] = ($porProveedor[$clave]['neto'] ?? 0) + $monto;
        }

        /*
         * Impuesto por documento: la factura suma 19% de IVA, la boleta de
         * honorarios resta 15,25% de retención y la factura exenta no
         * lleva. El cierre lo calcula igual, pero por OC.
         */
        $iva = 0;
        $retencion = 0;

        foreach ($porProveedor as &$datos) {
            $impuesto = CourierImpuestos::calcular($datos['tipo_documento'], $datos['neto']);

            $datos['iva'] = ($impuesto['impuesto'] ?? null) === CourierImpuestos::IVA ? $impuesto['valor_impuesto'] : 0;
            $datos['retencion'] = ($impuesto['impuesto'] ?? null) === CourierImpuestos::RETENCION ? $impuesto['valor_impuesto'] : 0;
            $datos['total'] = $datos['neto'] + $datos['iva'] - $datos['retencion'];

            $iva += $datos['iva'];
            $retencion += $datos['retencion'];
        }
        unset($datos);

        uasort($porProveedor, fn ($a, $b) => $b['neto'] <=> $a['neto']);

        $orden = ['RM' => 0, 'Regiones' => 1, 'Sin zona' => 2];
        uasort($porZona, fn ($a, $b) => ($orden[$a['zona']] ?? 9) <=> ($orden[$b['zona']] ?? 9));

        arsort($porTipo);

        /* Lo que quedó fuera por falta de una regla, no por decisión. */
        $bloqueados = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->whereIn('motivo', ['sin_comuna', 'comuna_desconocida', 'sin_rut_proveedor', 'sin_configuracion', 'llave_ambigua', 'sin_tabla', 'sin_tarifa', 'configuracion_revisar', 'peumo_sin_guia', 'peumo_sin_tarifa'])
            ->count();

        $motivos = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->where('estado_pago', 'DESCONTAR')
            ->selectRaw('motivo, COUNT(*) AS bultos')
            ->groupBy('motivo')
            ->orderByDesc('bultos')
            ->get()
            ->mapWithKeys(fn ($f) => [$f->motivo ?? 'sin motivo' => (int) $f->bultos])
            ->all();

        return [
            'calculado_at' => CourierBulto::query()->delPeriodo($periodo->id)->max('calculado_at'),
            'bultos_calculados' => $calculados,
            'bultos_pagados' => $bultos,
            'neto' => $neto,
            'iva' => $iva,
            'retencion' => $retencion,
            'total' => $neto + $iva - $retencion,
            'cierre' => CourierCierre::where('courier_periodo_id', $periodo->id)->first(),
            'por_zona' => array_values($porZona),
            'por_tipo' => $porTipo,
            'por_proveedor' => $porProveedor,
            'bloqueados' => $bloqueados,
            'motivos' => $motivos,
            'sin_calcular' => CourierBulto::query()->delPeriodo($periodo->id)->whereNull('calculado_at')->count(),
        ];
    }

    /*
     * Cómo se reparten los bultos del período entre los agentes, según
     * la comuna de destino. Es la antesala del resumen de pago: tiene
     * la misma forma (zona → agente), pero cuenta bultos, no pesos.
     * No calcula nada; solo cruza la comuna con el catálogo.
     */
    public function distribucion(CourierPeriodo $periodo): array
    {
        $estadosCatalogo = CourierEstadoEntrega::pluck('considerar', 'estado')->all();

        $cobertura = CourierCoberturaComuna::query()
            ->with('agente:id,nombre')
            ->get(['localidad_clave', 'zona', 'courier_agente_id'])
            ->mapWithKeys(fn ($c) => [
                $c->localidad_clave => [
                    'agente_id' => $c->courier_agente_id,
                    'agente' => $c->agente?->nombre ?? '—',
                    'zona' => $c->zona,
                ],
            ])
            ->all();

        /*
         * Se agrupa la comuna en binario para no juntar variantes que
         * el catálogo trata como distintas (Maipu / Maipú).
         */
        $filas = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->selectRaw('comuna_destino COLLATE utf8mb4_bin AS comuna, estado_entrega AS estado, COUNT(*) AS n')
            ->groupBy('comuna', 'estado')
            ->get();

        $agentes = [];
        $comunasNoReconocidas = [];
        $sinComuna = 0;
        $noReconocidas = 0;
        $total = 0;

        foreach ($filas as $fila) {
            $cantidad = (int) $fila->n;
            $total += $cantidad;

            $descuenta = ($estadosCatalogo[$fila->estado] ?? null) === CourierEstadoEntrega::DESCONTAR;

            if ($fila->comuna === null || $fila->comuna === '') {
                $sinComuna += $cantidad;

                continue;
            }

            $datos = $cobertura[CourierCoberturaComuna::clave($fila->comuna)] ?? null;

            if ($datos === null) {
                $noReconocidas += $cantidad;
                $comunasNoReconocidas[$fila->comuna] = ($comunasNoReconocidas[$fila->comuna] ?? 0) + $cantidad;

                continue;
            }

            $zona = $datos['zona'] ?: 'Sin zona';
            $llave = $zona . '|' . $datos['agente_id'];

            $agentes[$llave] ??= [
                'zona' => $zona,
                'agente_id' => $datos['agente_id'],
                'agente' => $datos['agente'],
                'bultos' => 0,
                'con_estado_pagable' => 0,
                'con_estado_descontado' => 0,
            ];

            $agentes[$llave]['bultos'] += $cantidad;
            $agentes[$llave][$descuenta ? 'con_estado_descontado' : 'con_estado_pagable'] += $cantidad;
        }

        /* Orden de zonas como en la planilla: RM, Regiones, el resto. */
        $orden = ['RM' => 0, 'Regiones' => 1, 'Sin zona' => 2];
        $zonas = [];

        foreach ($agentes as $datos) {
            $zonas[$datos['zona']]['zona'] = $datos['zona'];
            $zonas[$datos['zona']]['bultos'] = ($zonas[$datos['zona']]['bultos'] ?? 0) + $datos['bultos'];
            $zonas[$datos['zona']]['agentes'][] = $datos;
        }

        uasort($zonas, fn ($a, $b) => ($orden[$a['zona']] ?? 9) <=> ($orden[$b['zona']] ?? 9));

        foreach ($zonas as &$zona) {
            usort($zona['agentes'], fn ($a, $b) => $b['bultos'] <=> $a['bultos']);
        }
        unset($zona);

        arsort($comunasNoReconocidas);

        return [
            'total' => $total,
            'zonas' => array_values($zonas),
            'con_agente' => $total - $sinComuna - $noReconocidas,
            'sin_comuna' => $sinComuna,
            'comuna_no_reconocida' => $noReconocidas,
            'comunas_no_reconocidas' => $comunasNoReconocidas,
        ];
    }

    /*
     * Bultos del período a los que les falta la llave de pago, por
     * «agente | comerciante | servicio».
     *
     * Hace la pregunta del cálculo (CourierRevisionLlaves): RUT del
     * proveedor + RUT del cliente + servicio, y no la combinación escrita
     * del catálogo viejo (courier_configuracions). Como el cálculo, deja
     * fuera lo que saca antes de mirar la llave: los controles (pagado el
     * mes anterior, Blue, especial) y los retornos. Los bultos de comuna no
     * reconocida tienen su propia alerta.
     *
     * Para los repartidores de 4N el RUT depende de quién entregó, así que
     * el repartidor entra en la agrupación; la etiqueta, no.
     *
     * @param  array<string, string>  $cobertura  localidad_clave => nombre del agente
     * @param  array<string, mixed>  $descartan  estados que descuentan, por nombre
     * @return array<string, array{etiqueta: string, bultos: int, vivos: int}>
     */
    private function bultosSinLlave(CourierPeriodo $periodo, array $cobertura, array $descartan): array
    {
        $revision = CourierRevisionLlaves::desdeBase($cobertura);

        $grupos = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->whereNotNull('comuna_destino')
            ->whereNotExists(function ($q) use ($periodo) {
                $q->select(DB::raw(1))
                    ->from('courier_controles')
                    ->whereColumn('courier_controles.seguimiento', 'courier_bultos.seguimiento')
                    ->where('courier_controles.courier_periodo_id', $periodo->id)
                    ->whereIn('courier_controles.tipo', [
                        CourierControl::PAGADO_MES_ANTERIOR,
                        CourierControl::BLUE,
                        CourierControl::ESPECIAL,
                    ]);
            })
            ->selectRaw(
                'comuna_destino COLLATE utf8mb4_bin AS comuna, '
                . 'comerciante COLLATE utf8mb4_bin AS comerciante_bin, '
                . 'servicio COLLATE utf8mb4_bin AS servicio_bin, '
                . 'repartidor_nombre COLLATE utf8mb4_bin AS repartidor_bin, '
                . '(destinatario_nombre REGEXP ?) AS es_retorno, '
                . 'estado_entrega, COUNT(*) AS n',
                ['(?i)' . CourierCalculoService::RETORNO_REGEX]
            )
            ->groupBy('comuna', 'comerciante_bin', 'servicio_bin', 'repartidor_bin', 'es_retorno', 'estado_entrega')
            ->get();

        $sinLlave = [];

        foreach ($grupos as $grupo) {
            $agente = $cobertura[CourierCoberturaComuna::clave($grupo->comuna)] ?? null;

            if ($agente === null) {
                continue;
            }

            $etiqueta = $revision->faltante(
                $agente,
                $grupo->comerciante_bin,
                $grupo->servicio_bin,
                $grupo->repartidor_bin,
                (bool) $grupo->es_retorno
            );

            if ($etiqueta === null) {
                continue;
            }

            $cantidad = (int) $grupo->n;

            $sinLlave[$etiqueta]['etiqueta'] = $etiqueta;
            $sinLlave[$etiqueta]['bultos'] = ($sinLlave[$etiqueta]['bultos'] ?? 0) + $cantidad;
            $sinLlave[$etiqueta]['vivos'] = ($sinLlave[$etiqueta]['vivos'] ?? 0)
                + (isset($descartan[(string) $grupo->estado_entrega]) ? 0 : $cantidad);
        }

        return $sinLlave;
    }

    /*
     * Hasta tres bultos de ejemplo por comuna no reconocida.
     *
     * El nombre que manda Geolice a veces no dice nada por sí solo
     * (llega a venir un número). Con el cliente y la dirección,
     * Operaciones puede reconocer de qué envíos se trata.
     */
    private function ejemplosPorComuna(CourierPeriodo $periodo, array $comunas): array
    {
        if ($comunas === []) {
            return [];
        }

        $ejemplos = [];

        CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->whereIn('comuna_destino', $comunas)
            ->select(['seguimiento', 'comuna_destino', 'comerciante', 'servicio', 'direccion'])
            ->orderBy('id')
            ->chunk(1000, function ($bultos) use (&$ejemplos) {
                foreach ($bultos as $bulto) {
                    $comuna = $bulto->comuna_destino;

                    if (count($ejemplos[$comuna] ?? []) >= 3) {
                        continue;
                    }

                    $ejemplos[$comuna][] = [
                        'seguimiento' => $bulto->seguimiento,
                        'comerciante' => trim((string) $bulto->comerciante),
                        'servicio' => trim((string) $bulto->servicio),
                        'direccion' => $bulto->direccion,
                    ];
                }
            });

        return $ejemplos;
    }

    /* Mayor cantidad primero, conservando las claves. */
    private function ordenar(array $lista): array
    {
        arsort($lista);

        return $lista;
    }



    private function detectarDelimitadorCsv(string $ruta): string
    {
        $archivo = fopen($ruta, 'r');

        if ($archivo === false) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV.');
        }

        $primeraLinea = fgets($archivo);

        fclose($archivo);

        if ($primeraLinea === false) {
            throw new \RuntimeException('El archivo CSV está vacío.');
        }

        $comas = substr_count($primeraLinea, ',');
        $puntoComas = substr_count($primeraLinea, ';');

        return $puntoComas > $comas ? ';' : ',';
    }

    /*
     * Paso 1 del recorrido: ¿es el archivo correcto?
     *
     * Existe porque no había forma de saber qué descarga estaba cargada:
     * se llegó a contrastar el cálculo contra la planilla de Operaciones
     * usando una descarga que no correspondía al mes, y nadie se dio
     * cuenta hasta mirar la base a mano. El reparto por mes de recepción
     * es lo que delata ese caso.
     *
     * Sólo lee.
     */
    public function resumenDelArchivo(CourierPeriodo $periodo): array
    {
        $ultima = CourierImportacion::query()
            ->where('courier_periodo_id', $periodo->id)
            ->where('tipo', CourierImportacion::TIPO_GEOLICE)
            ->orderByDesc('id')
            ->first(['archivo', 'filas', 'created_at']);

        $bultos = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->selectRaw('COUNT(*) total')
            ->selectRaw('MIN(fecha_recepcion) desde')
            ->selectRaw('MAX(fecha_recepcion) hasta')
            ->selectRaw('MAX(calculado_at) calculado_at')
            ->selectRaw('SUM(calculado_at IS NULL) sin_calcular')
            ->first();

        $total = (int) ($bultos->total ?? 0);

        return [
            'archivo' => $ultima?->archivo,
            'importado_at' => $ultima?->created_at,
            'bultos' => $total,
            'sin_calcular' => (int) ($bultos->sin_calcular ?? 0),
            'desde' => $bultos->desde ?? null,
            'hasta' => $bultos->hasta ?? null,
            'calculado_at' => $bultos->calculado_at ?? null,
            /*
             * Los pesos de balanza y los controles no vienen de Geolice:
             * salen de la planilla de Operaciones y sobreviven a que se
             * vacíen los bultos.
             */
            'pesajes' => CourierPesaje::query()
                ->where('courier_periodo_id', $periodo->id)
                ->count(),
            /*
             * Reparto por mes de la fecha de recepción. Si el mes que se
             * paga no aparece acá, la descarga cargada no corresponde.
             */
            'meses' => $total === 0 ? [] : CourierBulto::query()
                ->delPeriodo($periodo->id)
                ->whereNotNull('fecha_recepcion')
                ->selectRaw("DATE_FORMAT(fecha_recepcion, '%Y-%m') mes, COUNT(*) bultos")
                ->groupBy('mes')
                ->orderBy('mes')
                ->get()
                ->map(fn ($f) => ['mes' => $f->mes, 'bultos' => (int) $f->bultos])
                ->all(),
            'estado' => match (true) {
                $total === 0 => 'sin_archivo',
                (int) ($bultos->sin_calcular ?? 0) > 0 => 'sin_calcular',
                default => 'listo',
            },
        ];
    }

    /*
     * Paso 2 del recorrido: ¿qué no se pudo reconocer?
     *
     * Igual que alertas(), pero separando lo que cuesta plata de lo que
     * no. Un bulto con comuna desconocida y estado Anulado no se iba a
     * pagar de todas formas; uno Entregado sí. Mostrarlos juntos obliga
     * a ir al Excel a averiguar cuál es cuál.
     *
     * "Vivo" = su estado de entrega no lo descarta. Un estado que no
     * está en el catálogo cuenta como vivo: nadie decidió descartarlo.
     *
     * No depende del cálculo: esta pantalla se ve antes de calcular, así
     * que no puede apoyarse en la columna motivo.
     */
    public function loQueNoSeReconoce(CourierPeriodo $periodo): array
    {
        $bultos = CourierBulto::query()->delPeriodo($periodo->id);

        $descartan = CourierEstadoEntrega::query()
            ->where('considerar', 'DESCONTAR')
            ->pluck('estado')
            ->flip()
            ->all();

        $vivo = fn (?string $estado) => ! isset($descartan[(string) $estado]);

        $sinComuna = (clone $bultos)
            ->whereNull('comuna_destino')
            ->selectRaw('estado_entrega, COUNT(*) AS n')
            ->groupBy('estado_entrega')
            ->get();

        $cobertura = CourierCoberturaComuna::query()
            ->with('agente:id,nombre')
            ->get(['localidad_clave', 'courier_agente_id'])
            ->mapWithKeys(fn ($c) => [$c->localidad_clave => $c->agente?->nombre ?? ''])
            ->all();

        /* Colación binaria: "CONCÓN" y "Concón" no deben caer juntos. */
        $grupos = (clone $bultos)
            ->whereNotNull('comuna_destino')
            ->selectRaw('comuna_destino COLLATE utf8mb4_bin AS comuna, estado_entrega, COUNT(*) AS n')
            ->groupBy('comuna', 'estado_entrega')
            ->get();

        $comunas = [];

        foreach ($grupos as $grupo) {
            if (isset($cobertura[CourierCoberturaComuna::clave($grupo->comuna)])) {
                continue;
            }

            $cantidad = (int) $grupo->n;
            $vivos = $vivo($grupo->estado_entrega) ? $cantidad : 0;

            $comunas[$grupo->comuna]['etiqueta'] = $grupo->comuna;
            $comunas[$grupo->comuna]['bultos'] = ($comunas[$grupo->comuna]['bultos'] ?? 0) + $cantidad;
            $comunas[$grupo->comuna]['vivos'] = ($comunas[$grupo->comuna]['vivos'] ?? 0) + $vivos;
        }

        /* La llave es la del cálculo, no el catálogo viejo: ver bultosSinLlave(). */
        $sinConfiguracion = $this->bultosSinLlave($periodo, $cobertura, $descartan);

        /* Lo que cuesta plata arriba; lo que no, abajo. */
        $ordenar = function (array $lista): array {
            uasort($lista, fn ($a, $b) => [$b['vivos'], $b['bultos']] <=> [$a['vivos'], $a['bultos']]);

            return $lista;
        };

        $comunas = $ordenar($comunas);
        $sinConfiguracion = $ordenar($sinConfiguracion);

        $bloques = [
            'sin_comuna' => [
                'bultos' => (int) $sinComuna->sum('n'),
                'vivos' => (int) $sinComuna->filter(fn ($f) => $vivo($f->estado_entrega))->sum('n'),
                'detalle' => [],
            ],
            'comuna_desconocida' => [
                'bultos' => array_sum(array_column($comunas, 'bultos')),
                'vivos' => array_sum(array_column($comunas, 'vivos')),
                'detalle' => array_values($comunas),
            ],
            'sin_configuracion' => [
                'bultos' => array_sum(array_column($sinConfiguracion, 'bultos')),
                'vivos' => array_sum(array_column($sinConfiguracion, 'vivos')),
                'detalle' => array_values($sinConfiguracion),
            ],
        ];

        return [
            'total' => (clone $bultos)->count(),
            'bultos' => array_sum(array_column($bloques, 'bultos')),
            'vivos' => array_sum(array_column($bloques, 'vivos')),
            'bloques' => $bloques,
            'ejemplos' => $this->ejemplosPorComuna($periodo, array_keys($comunas)),
            'estados_que_descartan' => array_keys($descartan),
        ];
    }

    /*
     * Paso 4 del recorrido: ¿con qué peso se pagó?
     *
     * Sólo mira los bultos que sí se pagan: el peso de los demás no
     * cuesta nada. El kilo es plata, y los que se pagan con el kilo por
     * defecto se pierden en silencio, así que van separados.
     */
    public function origenDeLosPesos(CourierPeriodo $periodo): ?array
    {
        $filas = CourierBulto::query()
            ->delPeriodo($periodo->id)
            ->where('estado_pago', 'PAGAR')
            ->selectRaw('origen_peso, COUNT(*) bultos, SUM(peso_pago) kilos, SUM(valor) monto')
            ->groupBy('origen_peso')
            ->get();

        if ($filas->isEmpty()) {
            return null;
        }

        $nombres = [
            'bodega' => 'Pesado en bodega',
            'declarado' => 'Peso que declaró el cliente',
            'x' => 'Sin peso: se pagó 1 kilo',
        ];

        $grupos = [];
        $bultos = 0;
        $monto = 0;

        foreach (['bodega', 'declarado', 'x'] as $origen) {
            $fila = $filas->firstWhere('origen_peso', $origen);

            $grupos[] = [
                'origen' => $origen,
                'nombre' => $nombres[$origen],
                'bultos' => (int) ($fila->bultos ?? 0),
                'kilos' => (int) ($fila->kilos ?? 0),
                'monto' => (int) ($fila->monto ?? 0),
            ];

            $bultos += (int) ($fila->bultos ?? 0);
            $monto += (int) ($fila->monto ?? 0);
        }

        return [
            'grupos' => $grupos,
            'bultos' => $bultos,
            'monto' => $monto,
        ];
    }
}
