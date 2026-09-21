<?php

namespace App\Imports\Courier;

use App\Models\CourierBulto;
use App\Models\CourierPesaje;
use App\Models\CourierPeriodo;

/*
 * CSV de pesajes de bodega ("Proceso del dia dd-mm-aaaa.csv") → courier_pesajes.
 *
 * Formato: Codigo;Notas;Cod_seguimiento
 *   Codigo          = código con sufijo (igual a courier_bultos.seguimiento)
 *   Notas           = kilos enteros de la balanza
 *   Cod_seguimiento = código sin sufijo
 *
 * No usa Laravel Excel: es un CSV chico con separador ";" y se lee con
 * fgetcsv. Cada pesaje se guarda con su fecha; un mismo bulto puede
 * pesarse en días distintos y eso lo resuelve el cálculo, no la carga.
 */
class PesajesImport
{
    /* 4N202608148928-529, SH202608218116-313 */
    public const PATRON_CODIGO = '/^[A-Z0-9]{2,4}\d{12}-\d{3}$/i';

    public array $resumen = [
        'filas' => 0,
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambio' => 0,
        'kilos_cero' => 0,
        'codigos_invalidos' => 0,
        'kilos_invalidos' => 0,
        'repetidos_en_archivo' => 0,
        'con_bulto' => 0,
        'sin_bulto' => 0,
    ];

    /** @var array<string,int> texto inválido en la columna Codigo → veces */
    public array $codigosInvalidos = [];

    /** @var array<string,int> código pesado que no existe en courier_bultos → kilos */
    public array $sinBulto = [];

    public function __construct(
        private CourierPeriodo $periodo,
        private string $fechaPesaje,
        private string $archivoOrigen,
    ) {
    }

    public function importar(string $ruta): void
    {
        $archivo = fopen($ruta, 'r');

        if ($archivo === false) {
            throw new \RuntimeException("No se pudo abrir el archivo {$this->archivoOrigen}.");
        }

        /* seguimiento → kilos. Si el archivo repite un código, la última fila manda. */
        $filas = [];

        while (($campos = fgetcsv($archivo, 0, ';', '"', '')) !== false) {
            $codigo = trim((string) ($campos[0] ?? ''));

            if ($codigo === '' || strcasecmp($codigo, 'Codigo') === 0) {
                continue; // fila vacía o encabezado
            }

            $this->resumen['filas']++;

            if (! preg_match(self::PATRON_CODIGO, $codigo)) {
                $this->resumen['codigos_invalidos']++;
                $texto = $this->legible($codigo);
                $this->codigosInvalidos[$texto] = ($this->codigosInvalidos[$texto] ?? 0) + 1;

                continue;
            }

            $kilos = trim((string) ($campos[1] ?? ''));

            if (! ctype_digit($kilos)) {
                $this->resumen['kilos_invalidos']++;

                continue;
            }

            $codigo = strtoupper($codigo);

            if (isset($filas[$codigo])) {
                $this->resumen['repetidos_en_archivo']++;
            }

            $filas[$codigo] = (int) $kilos;
        }

        fclose($archivo);

        if ($filas === []) {
            return;
        }

        $this->resumen['kilos_cero'] = count(array_filter($filas, fn ($k) => $k === 0));

        $this->guardar($filas);
        $this->cruzarConBultos($filas);
    }

    private function guardar(array $filas): void
    {
        $ahora = now()->format('Y-m-d H:i:s');

        foreach (array_chunk($filas, 1000, true) as $lote) {
            $existentes = CourierPesaje::query()
                ->whereIn('seguimiento', array_keys($lote))
                ->where('fecha_pesaje', $this->fechaPesaje)
                ->get()
                ->keyBy('seguimiento');

            $nuevos = [];

            foreach ($lote as $seguimiento => $kilos) {
                $pesaje = $existentes->get($seguimiento);

                if ($pesaje === null) {
                    $nuevos[] = [
                        'courier_periodo_id' => $this->periodo->id,
                        'seguimiento' => $seguimiento,
                        'codigo' => strtok($seguimiento, '-'),
                        'fecha_pesaje' => $this->fechaPesaje,
                        'kilos' => $kilos,
                        'archivo_origen' => $this->archivoOrigen,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                    $this->resumen['nuevos']++;

                    continue;
                }

                $pesaje->fill(['kilos' => $kilos, 'archivo_origen' => $this->archivoOrigen]);

                if ($pesaje->isDirty()) {
                    $pesaje->save();
                    $this->resumen['actualizados']++;
                } else {
                    $this->resumen['sin_cambio']++;
                }
            }

            if ($nuevos !== []) {
                CourierPesaje::insert($nuevos);
            }
        }
    }

    /*
     * Diagnóstico: cuántos códigos pesados existen ya como bulto. Los
     * que no, suelen ser bultos que Geolice entregará en la próxima
     * descarga; se guardan igual y se reportan.
     */
    private function cruzarConBultos(array $filas): void
    {
        $encontrados = [];

        foreach (array_chunk(array_keys($filas), 1000) as $lote) {
            foreach (CourierBulto::query()->whereIn('seguimiento', $lote)->pluck('seguimiento') as $seguimiento) {
                $encontrados[strtoupper($seguimiento)] = true;
            }
        }

        foreach ($filas as $codigo => $kilos) {
            if (isset($encontrados[$codigo])) {
                $this->resumen['con_bulto']++;
            } else {
                $this->resumen['sin_bulto']++;
                $this->sinBulto[$codigo] = $kilos;
            }
        }
    }

    /* El CSV viene en Windows-1252; "#¡VALOR!" trae bytes que no son UTF-8. */
    private function legible(string $texto): string
    {
        if (! mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
        }

        return mb_substr($texto, 0, 50);
    }
}
