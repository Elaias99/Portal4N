<?php

namespace App\Imports\Courier;

use App\Models\CourierBulto;
use App\Models\CourierCoberturaComuna;
use App\Models\CourierConfiguracion;
use App\Models\CourierEstadoEntrega;
use App\Models\CourierPeriodo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;

/*
 * Descarga de Geolice (export-NNNN-packages.xlsx) → courier_bultos.
 *
 * Se lee por posición de columna (A..AE), igual que los catálogos, y
 * por lotes porque la descarga trae decenas de miles de filas.
 *
 * Además de guardar, detecta qué no calza con los catálogos y lo deja
 * en los arreglos públicos para que el comando lo muestre. No escribe
 * nada del cálculo (agente, tabla, kilos): eso es del paso siguiente.
 */
class GeoliceBultosImport implements ToCollection, WithChunkReading, WithStartRow, WithCustomCsvSettings
{
    public array $resumen = [
        'filas' => 0,
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambio' => 0,
        'de_periodo_anterior' => 0,
        'con_peso_declarado' => 0,
        'sin_peso_declarado' => 0,
        'sin_comuna' => 0,
        'comuna_fuera_de_catalogo' => 0,
        'sin_configuracion' => 0,
        'fechas_no_reconocidas' => 0,
    ];

    /** @var array<string,int> estado de entrega → bultos */
    public array $estados = [];

    /** @var array<string,int> estados que no existen en courier_estados_entrega */
    public array $estadosDesconocidos = [];

    /** @var array<string,int> comuna de destino → bultos, cuando no está en cobertura */
    public array $comunasFueraDeCatalogo = [];

    /** @var array<string,int> "agente | comerciante | servicio" → bultos, cuando no hay configuración */
    public array $sinConfiguracion = [];

    /** @var array<string,string> localidad_clave → nombre del agente */
    private array $cobertura;

    /** @var array<string,int> llave de configuración → existe */
    private array $configuraciones;

    /** @var array<string,int> estado → existe */
    private array $estadosCatalogo;





    public function __construct(
        private CourierPeriodo $periodo,
        private string $archivoOrigen,
        private string $delimitadorCsv = ',',
    ) {
        $this->cobertura = CourierCoberturaComuna::query()
            ->with('agente:id,nombre')
            ->get(['localidad_clave', 'courier_agente_id'])
            ->mapWithKeys(fn ($c) => [
                $c->localidad_clave => $c->agente?->nombre ?? ''
            ])
            ->all();

        $this->configuraciones = CourierConfiguracion::pluck('llave')
            ->flip()
            ->all();

        $this->estadosCatalogo = CourierEstadoEntrega::pluck('estado')
            ->flip()
            ->all();
    }



    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->delimitadorCsv,
        ];
    }




    public function startRow(): int
    {
        return 2; // la fila 1 es el encabezado
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    public function collection(Collection $rows): void
    {
        $filas = [];

        foreach ($rows as $row) {
            $seguimiento = $this->texto($row[0] ?? null);

            if ($seguimiento === null) {
                continue;
            }

            $this->resumen['filas']++;
            $filas[$seguimiento] = $this->mapear($row, $seguimiento);
        }

        if ($filas === []) {
            return;
        }

        $existentes = CourierBulto::whereIn('seguimiento', array_keys($filas))
            ->get()
            ->keyBy('seguimiento');

        $nuevos = [];
        $ahora = now()->format('Y-m-d H:i:s');

        foreach ($filas as $seguimiento => $datos) {
            $this->detectar($datos);

            $bulto = $existentes->get($seguimiento);

            if ($bulto === null) {
                $nuevos[] = $datos + [
                    'courier_periodo_id' => $this->periodo->id,
                    'archivo_origen' => $this->archivoOrigen,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
                $this->resumen['nuevos']++;

                continue;
            }

            /*
             * Ya existe de un período anterior: no se pisa. Qué hacer con
             * él (PagadoMesAnterior) lo decide el cálculo, no la carga.
             */
            if ((int) $bulto->courier_periodo_id !== (int) $this->periodo->id) {
                $this->resumen['de_periodo_anterior']++;

                continue;
            }

            // Mismo período: la descarga más nueva manda.
            $bulto->fill($datos + ['archivo_origen' => $this->archivoOrigen]);

            if ($bulto->isDirty()) {
                $bulto->save();
                $this->resumen['actualizados']++;
            } else {
                $this->resumen['sin_cambio']++;
            }
        }

        foreach (array_chunk($nuevos, 500) as $lote) {
            CourierBulto::insert($lote);
        }
    }

    /*
     * Columnas A..AE de la descarga → columnas de courier_bultos.
     * Comerciante, servicio y comuna van SIN trim: son parte de las
     * llaves de los catálogos y deben calzar byte a byte.
     */
    private function mapear(Collection $row, string $seguimiento): array
    {
        return [
            'seguimiento' => $seguimiento,
            'codigo' => $this->texto($row[5] ?? null) ?? strtok($seguimiento, '-'),
            'peso_declarado' => $this->kilos($row[1] ?? null),
            'largo' => $this->numero($row[2] ?? null),
            'ancho' => $this->numero($row[3] ?? null),
            'alto' => $this->numero($row[4] ?? null),
            'codigo_externo' => $this->texto($row[6] ?? null),
            'centro_costo' => $this->texto($row[7] ?? null),
            'orden_compra' => $this->texto($row[8] ?? null),
            'guia_despacho' => $this->texto($row[9] ?? null),
            'estado_entrega' => $this->texto($row[10] ?? null) ?? '',
            'intentos_entrega' => (int) ($row[11] ?? 0),
            'comerciante' => (string) ($row[12] ?? ''),
            'servicio' => (string) ($row[13] ?? ''),
            'campana' => $this->texto($row[14] ?? null),
            'destinatario_nombre' => $this->texto($row[15] ?? null),
            'destinatario_empresa' => $this->texto($row[16] ?? null),
            'direccion' => $this->texto($row[17] ?? null),
            'comuna_destino' => ($row[18] ?? '') === '' ? null : (string) $row[18],
            'destinatario_telefono' => $this->texto($row[19] ?? null),
            'destinatario_email' => $this->texto($row[20] ?? null),
            'valor_envio' => $this->dinero($row[21] ?? null),
            'fecha_recepcion' => $this->fechaHora($row[22] ?? null),
            'entrega_estimada' => $this->fecha($row[23] ?? null),
            'fecha_entrega' => $this->fechaHora($row[24] ?? null),
            'retiro_en_comerciante' => in_array(mb_strtolower(trim((string) ($row[25] ?? ''))), ['sí', 'si'], true) ? 1 : 0,
            'bodega_retiro' => $this->texto($row[26] ?? null),
            'ruta_entrega' => $this->texto($row[27] ?? null),
            'repartidor_nombre' => $this->texto($row[28] ?? null),
            'repartidor_telefono' => $this->texto($row[29] ?? null),
            'usuario_entrega' => $this->texto($row[30] ?? null),
        ];
    }

    /*
     * Cruces de diagnóstico contra los catálogos. Solo cuenta; no
     * escribe en el bulto.
     */
    private function detectar(array $datos): void
    {
        if ($datos['peso_declarado'] === null) {
            $this->resumen['sin_peso_declarado']++;
        } else {
            $this->resumen['con_peso_declarado']++;
        }

        $estado = $datos['estado_entrega'];
        $this->estados[$estado] = ($this->estados[$estado] ?? 0) + 1;

        if (! isset($this->estadosCatalogo[$estado])) {
            $this->estadosDesconocidos[$estado] = ($this->estadosDesconocidos[$estado] ?? 0) + 1;
        }

        if ($datos['comuna_destino'] === null) {
            $this->resumen['sin_comuna']++;

            return;
        }

        $clave = CourierCoberturaComuna::clave($datos['comuna_destino']);

        if (! isset($this->cobertura[$clave])) {
            $this->resumen['comuna_fuera_de_catalogo']++;
            $comuna = $datos['comuna_destino'];
            $this->comunasFueraDeCatalogo[$comuna] = ($this->comunasFueraDeCatalogo[$comuna] ?? 0) + 1;

            return;
        }

        $agente = $this->cobertura[$clave];
        $llave = CourierConfiguracion::llave($agente, $datos['comerciante'], $datos['servicio']);

        if (! isset($this->configuraciones[$llave])) {
            $this->resumen['sin_configuracion']++;
            $etiqueta = $agente . ' | ' . $datos['comerciante'] . ' | ' . $datos['servicio'];
            $this->sinConfiguracion[$etiqueta] = ($this->sinConfiguracion[$etiqueta] ?? 0) + 1;
        }
    }

    /* ---------- limpieza de formato ---------- */

    /*
     * Texto recortado, sin el apóstrofe con que Geolice antepone
     * teléfonos y otros valores ('+569…). Vacío → null.
     */
    private function texto(mixed $valor): ?string
    {
        $texto = ltrim(trim((string) ($valor ?? '')), "'");

        return $texto === '' ? null : mb_substr($texto, 0, 255);
    }

    private function numero(mixed $valor): ?float
    {
        $texto = str_replace(',', '.', trim((string) ($valor ?? '')));

        return is_numeric($texto) ? (float) $texto : null;
    }

    /* "0.31 kg" → 0.31 */
    private function kilos(mixed $valor): ?float
    {
        return $this->numero(str_ireplace('kg', '', (string) ($valor ?? '')));
    }

    /* "$6,133.74" → 6133.74 */
    private function dinero(mixed $valor): ?float
    {
        return $this->numero(str_replace(['$', ',', ' '], '', (string) ($valor ?? '')));
    }

    /* "14/08/2026 21:11" → "2026-08-14 21:11:00" */
    private function fechaHora(mixed $valor): ?string
    {
        return $this->carbon($valor, ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'])?->format('Y-m-d H:i:s');
    }

    /* "17/08/2026" → "2026-08-17" */
    private function fecha(mixed $valor): ?string
    {
        return $this->carbon($valor, ['d/m/Y', 'd/m/Y H:i'])?->format('Y-m-d');
    }

    private function carbon(mixed $valor, array $formatos): ?Carbon
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        // Por si la celda viene con formato de fecha de Excel en vez de texto.
        if (is_numeric($valor)) {
            return Carbon::instance(FechaExcel::excelToDateTimeObject((float) $valor));
        }

        $texto = trim((string) $valor);

        foreach ($formatos as $formato) {
            try {
                // "!" deja en cero las partes que el formato no trae.
                return Carbon::createFromFormat('!' . $formato, $texto);
            } catch (\Throwable) {
                continue;
            }
        }

        $this->resumen['fechas_no_reconocidas']++;

        return null;
    }
}