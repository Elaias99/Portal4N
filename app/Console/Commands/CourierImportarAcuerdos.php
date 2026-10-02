<?php

namespace App\Console\Commands;

use App\Models\CourierPeriodo;
use App\Services\Courier\CourierAcuerdosService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/*
 * Carga los Acuerdos del mes desde Base_Acuerdos.xlsx (hojas Calendario
 * y Base Acuerdos, la plantilla de LogisticaCL) y los calcula.
 *
 * Reemplaza los acuerdos que el período ya tuviera, así que se puede
 * volver a cargar el archivo corregido cuantas veces haga falta.
 */
class CourierImportarAcuerdos extends Command
{
    protected $signature = 'courier:importar-acuerdos
        {archivo : Ruta a Base_Acuerdos.xlsx}
        {--periodo= : Período de pago AAAAMM (ej. 202608)}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga los Acuerdos del mes desde Base_Acuerdos.xlsx y calcula costo × (días − inasistencias + adicionales) × factor.';

    public function handle(CourierAcuerdosService $acuerdos): int
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

        $periodo = CourierPeriodo::where('codigo', $codigo)->first();

        if ($periodo === null) {
            $this->error("No existe el período {$codigo}.");

            return self::FAILURE;
        }

        if ($periodo->estaCerrado()) {
            $this->error("El período {$periodo->nombre} está cerrado.");

            return self::FAILURE;
        }

        $this->info("Período {$periodo->nombre}. Leyendo " . basename($archivo) . '…');

        if ($soloMostrar) {
            DB::beginTransaction();
        }

        try {
            $r = $acuerdos->importar($archivo, basename($archivo), $periodo);
        } catch (\Throwable $e) {
            if ($soloMostrar) {
                DB::rollBack();
            }

            $this->error('No se cargó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Acuerdos', 'Cantidad'], [
            ['acuerdos cargados', $this->n($r['acuerdos'])],
            ['servicios en la matriz', $this->n($r['servicios'])],
            ['días del mes / feriados', $this->n($r['dias']) . ' / ' . $this->n($r['feriados'])],
            ['total calculado', '$' . $this->n($r['total'])],
            ['total que trae el archivo', '$' . $this->n($r['total_archivo'])],
            ['sin proveedor en el catálogo (por RUT)', $this->n($r['sin_proveedor'])],
            ['sin zona', $this->n($r['sin_zona'])],
        ]);

        if ($soloMostrar) {
            DB::rollBack();
            $this->warn('Modo --solo-mostrar: no se guardó nada.');

            return self::SUCCESS;
        }

        $this->info('Acuerdos guardados. Se ven en el paso 5 del recorrido.');

        return self::SUCCESS;
    }

    private function n($valor): string
    {
        return number_format((int) $valor, 0, ',', '.');
    }
}
