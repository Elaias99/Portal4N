<?php

namespace App\Console\Commands;

use App\Models\CourierPeriodo;
use App\Services\Courier\CourierCierreService;
use Illuminate\Console\Command;

/*
 * Cierre definitivo del mes. Primero muestra qué se va a pagar, en
 * cuántas OC y con qué impuestos, y qué queda fuera; después pide
 * confirmación. Lo cerrado no se puede deshacer.
 */
class CourierCerrar extends Command
{
    protected $signature = 'courier:cerrar
        {--periodo= : Período de pago AAAAMM (ej. 202608)}
        {--solo-mostrar : Sólo muestra cómo quedaría el cierre, sin guardar nada}';

    protected $description = 'Cierra el mes: asigna OC, calcula impuestos y guarda lo pagado para que no se pague dos veces. No se puede deshacer.';

    public function handle(CourierCierreService $cierres): int
    {
        $codigo = (string) $this->option('periodo');

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
            $this->error("El período {$periodo->nombre} ya está cerrado.");

            return self::FAILURE;
        }

        try {
            $cierre = $cierres->armar($periodo);
        } catch (\Throwable $e) {
            $this->error('No se puede cerrar: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Cierre de {$periodo->nombre}");

        $this->table(['Se paga', ''], [
            ['pagos', $this->n(count($cierre['pagos']))],
            ['órdenes de compra', $this->n($cierre['ordenes_compra'])],
            ['neto', '$' . $this->n($cierre['neto'])],
            ['IVA (factura, se suma)', '$' . $this->n($cierre['iva'])],
            ['retención (boleta, se resta)', '$' . $this->n($cierre['retencion'])],
            ['total a pagar', '$' . $this->n($cierre['total'])],
        ]);

        if ($cierre['fuera'] !== []) {
            $this->warn('Quedan fuera del cierre (se pueden pagar en un período siguiente):');
            $this->table(['Motivo', 'Registros', 'Monto'], collect($cierre['fuera'])
                ->map(fn ($f, $motivo) => [
                    CourierCierreService::FUERA[$motivo] ?? $motivo,
                    $this->n($f['registros']),
                    '$' . $this->n($f['monto']),
                ])
                ->values()
                ->all());
        }

        if ($this->option('solo-mostrar')) {
            $this->warn('Modo --solo-mostrar: no se guardó nada.');

            return self::SUCCESS;
        }

        if (! $this->confirm("El cierre no se puede deshacer. ¿Cerrar {$periodo->nombre}?", false)) {
            $this->line('No se cerró.');

            return self::SUCCESS;
        }

        try {
            $cierres->cerrar($periodo);
        } catch (\Throwable $e) {
            $this->error('No se cerró: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$periodo->nombre} quedó cerrado.");

        return self::SUCCESS;
    }

    private function n($valor): string
    {
        return number_format((int) $valor, 0, ',', '.');
    }
}
