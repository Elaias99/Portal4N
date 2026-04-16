<?php

namespace App\Exports;

use App\Models\DocumentoCompra;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
        $this->operaciones = collect($operacionesExport);
    }

    private function esPortalProveedor(?string $formaPago): bool
    {
        return mb_strtolower(trim((string) $formaPago)) === 'portal proveedor';
    }

    /**
     * Colección base del export
     */
    public function collection(): Collection
    {
        return $this->operaciones
            ->filter(function ($op) {
                if (!isset($op['documento_id'])) {
                    return false;
                }

                $documento = DocumentoCompra::with('cobranzaCompra')
                    ->find($op['documento_id']);

                if (!$documento || !$documento->cobranzaCompra) {
                    return false;
                }

                return !$this->esPortalProveedor($documento->cobranzaCompra->forma_pago ?? null);
            })
            ->values();
    }

    /**
     * Mapeo: 1 fila = 1 operación (pago o abono)
     */
    public function map($op): array
    {
        if (!isset($op['documento_id'])) {
            return [];
        }

        $documento = DocumentoCompra::with([
            'empresa',
            'cobranzaCompra.banco',
        ])->find($op['documento_id']);

        if (!$documento || !$documento->cobranzaCompra) {
            return [];
        }

        $cobranza = $documento->cobranzaCompra;

        if ($this->esPortalProveedor($cobranza->forma_pago ?? null)) {
            return [];
        }

        // =========================
        // CUENTA ORIGEN
        // =========================
        $cuentaOrigen = $documento?->empresa?->cta_corriente ?? '0';
        $moneda       = 'CLP';

        // =========================
        // CUENTA DESTINO
        // =========================
        $cuentaDestino = $cobranza->numero_cuenta ?? '';

        // =========================
        // BANCO
        // =========================
        $codigoBanco = $cobranza->banco_id
            ? str_pad($cobranza->banco_id, 2, '0', STR_PAD_LEFT)
            : '';

        // =========================
        // BENEFICIARIO
        // =========================
        $rutBeneficiario = $cobranza->rut_cliente
            ? preg_replace('/[^0-9kK]/', '', $cobranza->rut_cliente)
            : '';

        $nombreBeneficiario = $cobranza->razon_social ?? '';

        // =========================
        // MONTO REAL
        // =========================
        $monto = (int) ($op['monto'] ?? 0);

        // =========================
        // CORREO BENEFICIARIO
        // =========================
        $correoBeneficiario = $this->resolverCorreoBeneficiario(
            $cobranza->responsable ?? null
        );

        // =========================
        // FECHA DE PAGO / EXPORTACIÓN
        // =========================
        $fechaPagoFormateada = !empty($op['fecha'])
            ? Carbon::parse($op['fecha'])->format('dmY')
            : now()->format('dmY');

        // =========================
        // GLOSAS
        // =========================
        $glosaPago  = "Pago Proveedores {$fechaPagoFormateada}";
        $glosaFolio = "Pago Folio N {$documento->folio}";

        return [
            $cuentaOrigen,        // Cuenta origen
            $moneda,              // Moneda origen
            $cuentaDestino,       // Cuenta destino
            $moneda,              // Moneda destino
            $codigoBanco,         // Código banco destino
            $rutBeneficiario,     // RUT beneficiario
            $nombreBeneficiario,  // Nombre beneficiario
            $monto,               // Monto transferencia
            $glosaPago,           // Glosa personalizada transferencia
            $correoBeneficiario,  // Correo beneficiario
            $glosaFolio,          // Mensaje correo beneficiario
            $glosaPago,           // Glosa cartola originador
            $glosaFolio,          // Glosa cartola beneficiario
        ];
    }

    private function resolverCorreoBeneficiario(?string $responsable): ?string
    {
        if (!$responsable) {
            return null;
        }

        $responsableNormalizado = mb_strtolower(
            preg_replace('/\s+/', ' ', trim($responsable))
        );

        return match ($responsableNormalizado) {
            'hans de la barra' => 'proveedores@4nlogistica.cl',
            'luis de la barra' => 'proveedores@4nlogistica.cl',
            'natalia leyton' => 'finanzas@4nlogistica.cl',
            default => null,
        };
    }

    /**
     * Encabezados (idénticos a honorarios)
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
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => 'solid',
                    'startColor' => ['rgb' => '1F2937'],
                ],
            ],
            'A:Z' => [
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical'   => 'center',
                ],
            ],
        ];
    }
}