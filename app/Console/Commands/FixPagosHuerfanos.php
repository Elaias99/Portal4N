<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentoFinanciero;

class FixPagosHuerfanos extends Command
{
    protected $signature = 'fix:pagos-huerfanos';
    protected $description = 'Crea registros en la tabla pagos para documentos con status = Pago pero sin relación en pagos()';

    public function handle()
    {
        $this->info('🔍 Buscando documentos con estado "Pago" pero sin registro en pagos...');

        $documentos = DocumentoFinanciero::where('status', 'Pago')
            ->whereDoesntHave('pagos')
            ->get();

        if ($documentos->isEmpty()) {
            $this->info('✅ No se encontraron documentos huérfanos.');
            return;
        }

        $this->info("📄 Se encontraron {$documentos->count()} documentos. Procediendo a repararlos...");

        foreach ($documentos as $doc) {
            $fecha = $doc->fecha_estado_manual ?? now();

            $doc->pagos()->create([
                'fecha_pago' => $fecha,
                'user_id' => 1, // puedes cambiar este ID si quieres asignarlo a otro usuario
            ]);

            $this->line("✅ Documento ID {$doc->id} (folio {$doc->folio}) reparado con fecha {$fecha}");
        }

        $this->info('🎉 Reparación completada. Todos los pagos huérfanos fueron creados correctamente.');
    }
}

