<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentoFinanciero;

class InicializarSaldoPendiente extends Command
{
    protected $signature = 'finanzas:inicializar-saldo-pendiente';
    protected $description = 'Calcula el saldo pendiente actual mediante el accessor y lo guarda en la nueva columna';

    public function handle()
    {
        $this->info('⏳ Iniciando inicialización de saldo pendiente...');

        DocumentoFinanciero::chunk(500, function ($documentos) {
            foreach ($documentos as $doc) {

                // Leer el saldo real usando el accessor actual
                $saldoCalculado = $doc->getSaldoPendienteAttribute();

                // Guardarlo en la nueva columna
                $doc->update([
                    'saldo_pendiente' => $saldoCalculado,
                ]);
            }
        });

        $this->info('✅ Saldos pendientes inicializados correctamente.');
        return Command::SUCCESS;
    }
}

