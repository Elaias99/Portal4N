<?php

namespace App\Console\Commands;

use App\Imports\Courier\ControlesImport;
use App\Imports\Courier\FiltroColumnas;
use App\Imports\Courier\PesoRealImport;
use App\Models\CourierControl;
use App\Models\CourierPeriodo;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CourierImportarMes extends Command
{
    protected $signature = 'courier:importar-mes
        {archivo : Planilla mensual de Operaciones (AAAAMM_4N_COURIER_RESPALDOS.xlsx)}
        {--periodo= : Período de pago AAAAMM (ej. 202608)}
        {--hojas= : Sólo estas hojas, separadas por coma (ej. Retornos,Blue)}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga del Excel de Operaciones los datos del mes: pesos de bodega (PesoReal) y los controles que sacan bultos del pago.';

    public function handle(): int
    {
        $archivo = (string) $this->argument('archivo');
        $codigo = (string) $this->option('periodo');
        $soloMostrar = (bool) $this->option('solo-mostrar');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $codigo)) {
            $this->error('Indica el período con --periodo=AAAAMM (ej. --periodo=202608).');

            return self::FAILURE;
        }

        // El libro completo pesa decenas de MB y PesoReal trae más de 100 mil filas.
        set_time_limit(0);
        ini_set('memory_limit', '4096M');

        DB::beginTransaction();

        try {
            $periodo = CourierPeriodo::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'anio' => (int) substr($codigo, 0, 4),
                    'mes' => (int) substr($codigo, 4, 2),
                    'estado' => 'abierto',
                ]
            );

            if ($periodo->estaCerrado()) {
                DB::rollBack();
                $this->error("El período {$codigo} está cerrado; no se puede cargar sobre él.");

                return self::FAILURE;
            }

            $nombre = basename($archivo);

            $this->info("Período {$periodo->nombre}. Leyendo {$nombre}…");
            $this->line('Las hojas se abren de a una: juntas no caben en memoria.');

            /*
             * hoja => [importador, hasta qué columna hace falta leer]
             * Las columnas de cada hoja están documentadas en
             * storage/agents/courier/01-negocio.md.
             */
            $hojas = [
                'PesoReal' => [new PesoRealImport($periodo, $nombre), 4],
                'especiales' => [new ControlesImport($periodo, CourierControl::ESPECIAL, 5, 10, 'DESCONTAR', $nombre), 11],
                'Retornos' => [new ControlesImport($periodo, CourierControl::RETORNO, 0, 1, 'DESCONTAR', $nombre, 3), 4],
                'Blue' => [new ControlesImport($periodo, CourierControl::BLUE, 1, 6, 'DESCONTAR', $nombre), 7],
                'PagadosMesAnterior' => [new ControlesImport($periodo, CourierControl::PAGADO_MES_ANTERIOR, 2, 7, 'SI', $nombre), 8],
            ];

            $solo = array_filter(array_map('trim', explode(',', (string) $this->option('hojas'))));

            if ($solo !== []) {
                $hojas = array_intersect_key($hojas, array_flip($solo));

                if ($hojas === []) {
                    throw new \InvalidArgumentException('Ninguna de las hojas indicadas existe en la carga mensual.');
                }
            }

            foreach ($hojas as $hoja => [$import, $hastaColumna]) {
                $this->line("  · {$hoja}…");

                $import->collection($this->leerHoja($archivo, $hoja, $hastaColumna));

                $this->mostrar($hoja, $import->resumen);
            }

            if ($soloMostrar) {
                DB::rollBack();
                $this->warn('Modo --solo-mostrar: no se guardó nada.');
            } else {
                DB::commit();
                $this->info('Datos del mes guardados.');
                $this->line('Ahora puedes calcular el período con courier:calcular --periodo=' . $codigo);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falló la importación, no se guardó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /*
     * Carga una sola hoja, y sólo sus primeras columnas.
     *
     * `setReadDataOnly` hace que las celdas con fórmula entreguen el
     * último valor calculado que guardó Excel, que es justamente lo que
     * se necesita: en Retornos el código del bulto es una fórmula.
     */
    private function leerHoja(string $archivo, string $hoja, int $hastaColumna): Collection
    {
        $lector = IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $lector->setLoadSheetsOnly($hoja);
        $lector->setReadFilter(new FiltroColumnas($hastaColumna));

        $libro = $lector->load($archivo);
        $hoja = $libro->getActiveSheet();

        // Sin referencias de celda: arreglos simples, indexados desde 0.
        $filas = $hoja->toArray(null, false, false, false);

        /*
         * Las celdas con fórmula llegan como texto ("=+R2"), así que se
         * reemplazan por el último valor que calculó Excel. Pasa en
         * Retornos, donde el código del bulto y su valor son fórmulas.
         */
        foreach ($filas as $i => $fila) {
            foreach ($fila as $j => $valor) {
                if (is_string($valor) && str_starts_with($valor, '=')) {
                    $columna = Coordinate::stringFromColumnIndex($j + 1);
                    $filas[$i][$j] = $hoja->getCell($columna . ($i + 1))->getOldCalculatedValue();
                }
            }
        }

        $libro->disconnectWorksheets();
        unset($libro, $lector, $hoja);
        gc_collect_cycles();

        return collect($filas);
    }

    private function mostrar(string $titulo, array $resumen): void
    {
        $this->table(
            [$titulo, 'Cantidad'],
            collect($resumen)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );
    }
}
