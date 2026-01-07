<?php

namespace App\Exports;

use App\Models\DocumentoCompra;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PagosMasivosDocumentoCompraExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    protected Collection $operaciones;

    /**
     * @param array $operacionesExport
     */
    public function __construct(array $operacionesExport)
    {
        Log::info('[PAGOS_MASIVOS] Export constructor iniciado', [
            'cantidad_operaciones' => count($operacionesExport),
        ]);

        $this->operaciones = collect($operacionesExport);
    }

    /**
     * Colección base del export
     */
    public function collection()
    {
        Log::info('[PAGOS_MASIVOS] Export collection()', [
            'cantidad_operaciones' => $this->operaciones->count(),
        ]);

        return $this->operaciones;
    }

    /**
     * Mapeo fila por fila (UNA FILA = UNA OPERACIÓN)
     */
    public function map($op): array
    {
        // Validación defensiva
        if (!isset($op['documento_id'])) {
            Log::warning('[PAGOS_MASIVOS] Operación sin documento_id', [
                'operacion' => $op,
            ]);
            return [];
        }

        $documento = DocumentoCompra::with([
            'cobranzaCompra.banco',
            'cobranzaCompra.tipoCuenta',
        ])->find($op['documento_id']);

        if (!$documento) {
            Log::warning('[PAGOS_MASIVOS] Documento no encontrado en export', [
                'documento_id' => $op['documento_id'],
            ]);
            return [];
        }

        if (!$documento->cobranzaCompra) {
            Log::warning('[PAGOS_MASIVOS] Documento sin cobranza asociada en export', [
                'documento_id' => $op['documento_id'],
            ]);
            return [];
        }

        $cobranza = $documento->cobranzaCompra;

        // Cuenta origen
        $cuentaOrigen = '0';
        $moneda = 'CLP';

        // Destino
        $cuentaDestino = $cobranza->numero_cuenta ?? '';
        $codigoBanco = $cobranza->banco_id
            ? str_pad($cobranza->banco_id, 2, '0', STR_PAD_LEFT)
            : '';

        // Beneficiario
        $rutBeneficiario = $cobranza->rut_cliente
            ? preg_replace('/[^0-9kK]/', '', $cobranza->rut_cliente)
            : '';

        $nombreBeneficiario = $cobranza->razon_social ?? '';

        // Monto REAL (pago o abono)
        $monto = (int) ($op['monto'] ?? 0);

        // Glosa
        $tipo = ($op['tipo'] ?? '') === 'abono' ? 'Abono' : 'Pago';
        $glosa = "{$tipo} documento {$documento->folio}";

        return [
            $cuentaOrigen,
            $moneda,
            $cuentaDestino,
            $moneda,
            $codigoBanco,
            $rutBeneficiario,
            $nombreBeneficiario,
            $monto,
            $glosa,
            null,
            null,
            $glosa,
            null,
        ];
    }

    /**
     * Encabezados del Excel
     */
    public function headings(): array
    {
        Log::info('[PAGOS_MASIVOS] Export headings()');

        return [
            'Cuenta origen',
            'Moneda origen',
            'Cuenta destino',
            'Moneda destino',
            'Código banco destino',
            'RUT beneficiario',
            'Nombre beneficiario',
            'Monto transferencia',
            'Glosa personalizada transferencia',
            'Correo beneficiario',
            'Mensaje correo beneficiario',
            'Glosa cartola originador',
            'Glosa cartola beneficiario',
        ];
    }

    /**
     * Estilos
     */
    public function styles(Worksheet $sheet)
    {
        Log::info('[PAGOS_MASIVOS] Export styles()');

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '1F2937'],
                ],
            ],
            'A:Z' => [
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }
}
