<?php

namespace App\Console\Commands;

use App\Models\CourierPagoProceso;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierProcesosService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/*
 * Carga los pagos del mes de Ruta CV, Servicios, Visitas, Especiales o
 * Apoyo Alza desde su plantilla (la misma de LogisticaCL) y los calcula.
 *
 * Reemplaza lo que el período ya tuviera de ese proceso. Apoyo Alza va
 * al final, porque se calcula sobre Acuerdos, Ruta CV y Variables.
 */
class CourierImportarProceso extends Command
{
    protected $signature = 'courier:importar-proceso
        {proceso : ruta-cv | servicios | visitas | especiales | apoyo-alza}
        {archivo : Ruta a la plantilla del proceso (.xlsx)}
        {--periodo= : Período de pago AAAAMM (ej. 202608)}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga los pagos de Ruta CV, Servicios, Visitas, Especiales o Apoyo Alza desde su plantilla y los calcula.';

    public function handle(CourierProcesosService $procesos): int
    {
        $clave = (string) $this->argument('proceso');
        $archivo = (string) $this->argument('archivo');
        $codigo = (string) $this->option('periodo');
        $soloMostrar = (bool) $this->option('solo-mostrar');

        $proceso = CourierPagoProceso::PROCESOS[$clave] ?? null;

        if ($proceso === null) {
            $this->error('El proceso debe ser uno de: ' . implode(', ', array_keys(CourierPagoProceso::PROCESOS)) . '.');

            return self::FAILURE;
        }

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

        $this->info("{$proceso} · {$periodo->nombre}. Leyendo " . basename($archivo) . '…');

        if ($soloMostrar) {
            DB::beginTransaction();
        }

        try {
            $r = $procesos->importar($proceso, $archivo, basename($archivo), $periodo);
        } catch (\Throwable $e) {
            if ($soloMostrar) {
                DB::rollBack();
            }

            $this->error('No se cargó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        $filas = [
            ['filas cargadas', $this->n($r['filas'])],
            ['total', '$' . $this->n($r['total'])],
            ['sin proveedor en el catálogo (por RUT)', $this->n($r['sin_proveedor'])],
            ['sin zona', $this->n($r['sin_zona'])],
        ];

        foreach ($r['estados'] as $estado => $cantidad) {
            $filas[] = ['cálculo: ' . str_replace('_', ' ', $estado), $this->n($cantidad)];
        }

        $this->table([$proceso, 'Cantidad'], $filas);

        if ($soloMostrar) {
            DB::rollBack();
            $this->warn('Modo --solo-mostrar: no se guardó nada.');

            return self::SUCCESS;
        }

        $this->info("{$proceso} guardado. Se ve en el paso 5 del recorrido.");

        return self::SUCCESS;
    }

    private function n($valor): string
    {
        return number_format((int) $valor, 0, ',', '.');
    }
}
