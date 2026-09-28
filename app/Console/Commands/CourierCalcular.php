<?php

namespace App\Console\Commands;

use App\Models\CourierPeriodo;
use App\Services\Courier\CourierCalculoService;
use Illuminate\Console\Command;

class CourierCalcular extends Command
{
    protected $signature = 'courier:calcular {--periodo= : Período de pago AAAAMM (ej. 202608)}';

    protected $description = 'Aplica la cadena de pago a los bultos del período: agente, tabla, kilos, valor y si se paga o se descuenta.';

    public function handle(CourierCalculoService $calculo): int
    {
        $codigo = (string) $this->option('periodo');

        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $codigo)) {
            $this->error('Indica el período con --periodo=AAAAMM (ej. --periodo=202608).');

            return self::FAILURE;
        }

        $periodo = CourierPeriodo::query()->where('codigo', $codigo)->first();

        if ($periodo === null) {
            $this->error("No existe el período {$codigo}.");

            return self::FAILURE;
        }

        if ($periodo->estaCerrado()) {
            $this->error("El período {$periodo->nombre} está cerrado.");

            return self::FAILURE;
        }

        $this->info("Calculando {$periodo->nombre}…");

        $inicio = microtime(true);
        $resultado = $calculo->calcular($periodo);
        $segundos = round(microtime(true) - $inicio, 1);

        $r = $resultado['resumen'];

        $this->table(['Cálculo', 'Cantidad'], [
            ['bultos', number_format($r['bultos'], 0, ',', '.')],
            ['se pagan', number_format($r['pagar'], 0, ',', '.')],
            ['se descuentan', number_format($r['descontar'], 0, ',', '.')],
            ['monto neto', '$' . number_format($r['monto'], 0, ',', '.')],
            ['kilos de balanza', number_format($r['peso_bodega'], 0, ',', '.')],
            ['kilos declarados', number_format($r['peso_declarado'], 0, ',', '.')],
            ['sin peso (1 kg)', number_format($r['peso_por_defecto'], 0, ',', '.')],
        ]);

        $this->table(
            ['Por qué no se paga', 'Bultos'],
            collect($resultado['motivos'])
                ->map(fn ($n, $motivo) => [
                    CourierCalculoService::MOTIVOS[$motivo] ?? $motivo,
                    number_format($n, 0, ',', '.'),
                ])
                ->values()
                ->all()
        );

        $this->info("Listo en {$segundos} segundos.");

        return self::SUCCESS;
    }
}
