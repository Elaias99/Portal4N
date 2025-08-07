<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compra;
use Carbon\Carbon;

class RecalcularVencimientosCompras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compras:recalcular-vencimientos';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plazosDias = [
            'Contado' => 0,
            '1 Semana' => 7,
            'Quincena' => 15,
            '30 Días' => 30,
            '45 Días' => 45,
            '60 Días' => 60,
        ];

        $total = 0;
        $modificados = 0;

        $this->info('⏳ Recalculando fechas de vencimiento...');

        foreach (Compra::with('plazoPago')->get() as $compra) {
            $total++;

            $plazoNombre = $compra->plazoPago->nombre ?? null;

            // Si el plazo no está definido o no tiene fecha_documento, saltamos
            if (!isset($plazosDias[$plazoNombre]) || !$compra->fecha_documento) {
                continue;
            }

            $fechaDocumento = Carbon::parse($compra->fecha_documento);
            $dias = $plazosDias[$plazoNombre];

            if ($plazoNombre === 'Contado') {
                $vencimiento = $fechaDocumento;
            } else {
                $base = $fechaDocumento->copy()->addDays($dias);
                $vencimiento = $base->isFriday() ? $base : $base->copy()->next(Carbon::FRIDAY);
            }

            // Solo actualizamos si hay diferencia
            if ($compra->fecha_vencimiento !== $vencimiento->format('Y-m-d')) {
                $compra->fecha_vencimiento = $vencimiento;
                $compra->save();
                $modificados++;

                $this->line("✔ Compra ID {$compra->id} actualizada a {$vencimiento->format('Y-m-d')} ({$plazoNombre})");
            }
        }

        $this->info("✅ Proceso completado: $modificados de $total compras modificadas.");
    }
}
