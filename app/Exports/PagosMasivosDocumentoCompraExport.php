<?php

namespace App\Exports;

use App\Models\DocumentoCompra;
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
    protected $documentos;

    /**
     * @param array $documentosSeleccionados
     */
    public function __construct(array $documentosSeleccionados)
    {
        $this->documentos = DocumentoCompra::with([
                'cobranzaCompra.banco',
                'cobranzaCompra.tipoCuenta',
            ])
            ->whereIn('id', $documentosSeleccionados)
            ->get();
    }

    /**
     * Colección base del export
     */
    public function collection()
    {
        return $this->documentos;
    }

    /**
     * Mapeo fila por fila
     */
    public function map($documento): array
    {
        $cobranza = $documento->cobranzaCompra;

        // 🏦 Cuenta origen (por ahora fija / configurable)
        $cuentaOrigen = '0';
        $moneda = 'CLP';

        // 🏦 Datos destino
        $cuentaDestino = $cobranza->numero_cuenta ?? '';
        $codigoBanco = $cobranza->banco_id
            ? str_pad($cobranza->banco_id, 2, '0', STR_PAD_LEFT)
            : '';

        // 👤 Beneficiario
        $rutBeneficiario = $cobranza->rut_cliente
            ? preg_replace('/[^0-9kK]/', '', $cobranza->rut_cliente)
            : '';

        $nombreBeneficiario = $cobranza->razon_social ?? '';

        

        // 💰 Monto
        $monto = (int) $documento->monto_total;



        // 📝 Glosas
        $glosa = "Pago documento {$documento->folio}";

        return [
            $cuentaOrigen,
            $moneda,
            $cuentaDestino,
            $moneda,
            $codigoBanco,
            $rutBeneficiario,
            $nombreBeneficiario,
            $monto,
            $glosa,        // Glosa personalizada transferencia
            null,          // Correo beneficiario
            null,          // Mensaje correo beneficiario
            $glosa,        // Glosa cartola originador
            null,          // Glosa cartola beneficiario
        ];
    }

    /**
     * Encabezados del Excel
     */
    public function headings(): array
    {
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
