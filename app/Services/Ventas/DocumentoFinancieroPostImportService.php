<?php

namespace App\Services\Ventas;

use App\Models\DocumentoFinanciero;
use App\Support\Ventas\DocumentoFinancieroImportState;

class DocumentoFinancieroPostImportService
{
    public function execute(DocumentoFinancieroImportState $state): void
    {
        $state->notasCredito = [];

        $notas = DocumentoFinanciero::where('tipo_documento_id', 61)
            ->whereIn('folio', $state->importados)
            ->get();

        foreach ($notas as $nota) {
            $this->vincularNotaCredito($nota, $state, true);
        }

        $notasPendientes = DocumentoFinanciero::where('tipo_documento_id', 61)
            ->whereNull('referencia_id')
            ->whereNotNull('folio_docto_referencia')
            ->get();

        foreach ($notasPendientes as $nota) {
            $this->vincularNotaCredito($nota, $state, false);
        }

        $facturasAfectadas = DocumentoFinanciero::whereIn(
            'id',
            DocumentoFinanciero::where('tipo_documento_id', 61)
                ->whereNotNull('referencia_id')
                ->pluck('referencia_id')
        )->get();

        foreach ($facturasAfectadas as $factura) {
            $factura->recalcularSaldoPendiente();
            $factura->save();
        }
    }

    protected function vincularNotaCredito(
        DocumentoFinanciero $nota,
        DocumentoFinancieroImportState $state,
        bool $esImportada = true
    ): void {
        $factura = DocumentoFinanciero::where('folio', $nota->folio_docto_referencia)
            ->where('tipo_documento_id', $nota->tipo_docto_referencia)
            ->first();

        if ($factura) {
            if (!$nota->referencia_id) {
                $nota->referencia_id = $factura->id;
                $nota->save();
            }

            if (!$factura->referenciados()->where('id', $nota->id)->exists()) {
                $factura->referenciados()->save($nota);
            }

            $factura->refresh();
            $factura->recalcularSaldoPendiente();
            $factura->save();

            if ($esImportada) {
                $state->notasCredito[] = "✅ Nota de crédito folio {$nota->folio} vinculada correctamente a la factura {$factura->folio}.";
            }

            return;
        }

        if ($esImportada) {
            $state->notasCredito[] = "⚠️ Nota de crédito folio {$nota->folio} no pudo vincularse porque la factura referenciada aún no existe.";
        }
    }
}