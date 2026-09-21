<?php

namespace App\Services\Courier;

use App\Imports\Courier\GeoliceBultosImport;
use App\Imports\Courier\PesajesImport;
use App\Models\CourierBulto;
use App\Models\CourierCoberturaComuna;
use App\Models\CourierConfiguracion;
use App\Models\CourierEstadoEntrega;
use App\Models\CourierImportacion;
use App\Models\CourierPeriodo;
use App\Models\CourierPesaje;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as TipoExcel;
use Maatwebsite\Excel\Facades\Excel;

/*
 * Proceso mensual de pago Courier: períodos, cargas de datos y
 * diagnóstico de lo cargado contra los catálogos.
 *
 * Es la contraparte para pantalla de los comandos courier:importar-*;
 * ambos usan las mismas clases de importación.
 */
class CourierPagoService
{
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
        // Misma holgura que el comando: la descarga trae decenas de miles de filas.
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $nombre = $archivo->getClientOriginalName();
        $inicio = microtime(true);

        return DB::transaction(function () use ($archivo, $codigoPeriodo, $usuarioId, $nombre, $inicio) {
            $periodo = CourierPeriodo::firstOrCreate(
                ['codigo' => $codigoPeriodo],
                [
                    'anio' => (int) substr($codigoPeriodo, 0, 4),
                    'mes' => (int) substr($codigoPeriodo, 4, 2),
                    'estado' => 'abierto',
                ]
            );

            $import = new GeoliceBultosImport($periodo, $nombre);

            Excel::import($import, $archivo, null, TipoExcel::XLSX);

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

        $configuraciones = CourierConfiguracion::pluck('llave')->flip()->all();

        /*
         * Se agrupa en binario para que "CONCÓN" y "Concón" no caigan en
         * el mismo grupo: la colación por defecto ignoraría mayúsculas y
         * tildes y escondería justamente las variantes que buscamos.
         */
        $grupos = (clone $bultos)
            ->whereNotNull('comuna_destino')
            ->selectRaw(
                'comuna_destino COLLATE utf8mb4_bin AS comuna, '
                . 'comerciante COLLATE utf8mb4_bin AS comerciante_bin, '
                . 'servicio COLLATE utf8mb4_bin AS servicio_bin, '
                . 'COUNT(*) AS n'
            )
            ->groupBy('comuna', 'comerciante_bin', 'servicio_bin')
            ->get();

        $comunasFuera = [];
        $sinConfiguracion = [];

        foreach ($grupos as $grupo) {
            $clave = CourierCoberturaComuna::clave($grupo->comuna);

            if (! isset($cobertura[$clave])) {
                $comunasFuera[$grupo->comuna] = ($comunasFuera[$grupo->comuna] ?? 0) + (int) $grupo->n;

                continue;
            }

            $agente = $cobertura[$clave];
            $llave = CourierConfiguracion::llave($agente, $grupo->comerciante_bin, $grupo->servicio_bin);

            if (! isset($configuraciones[$llave])) {
                $etiqueta = $agente . ' | ' . trim($grupo->comerciante_bin) . ' | ' . trim($grupo->servicio_bin);
                $sinConfiguracion[$etiqueta] = ($sinConfiguracion[$etiqueta] ?? 0) + (int) $grupo->n;
            }
        }

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
            'sin_configuracion' => $this->ordenar($sinConfiguracion),
            'sin_configuracion_bultos' => array_sum($sinConfiguracion),
        ];
    }

    /* Mayor cantidad primero, conservando las claves. */
    private function ordenar(array $lista): array
    {
        arsort($lista);

        return $lista;
    }
}
