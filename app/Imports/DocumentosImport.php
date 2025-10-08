<?php

namespace App\Imports;

use App\Models\DocumentoFinanciero;
use App\Models\Cobranza;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Throwable;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class DocumentosImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public $errores = [];
    public $importados = [];
    public $duplicados = [];
    public $sinCobranza = [];
    public $notasCredito = [];


    protected $empresaId;

    public function __construct($empresaId = null)
    {
        $this->empresaId = $empresaId;
    }


    protected $encabezadosEsperados = [
        'folio', 'nro', 'tipo_doc', 'tipo_venta', 'rut_cliente', 'razon_social',
        'fecha_docto', 'fecha_recepcion', 'fecha_acuse_recibo', 'fecha_reclamo',
        'monto_exento', 'monto_neto', 'monto_iva', 'monto_total'
        // ... puedes agregar aquí las demás columnas clave
    ];


    public function model(array $row)
    {
        // 🔹 Evitar procesar filas completamente vacías
        if (empty(array_filter($row))) {
            return null;
        }

        // 🔹 Verificar estructura (solo la primera vez)
        static $estructuraVerificada = false;
        if (!$estructuraVerificada) {
            $estructuraVerificada = true;

            $columnasArchivo = array_keys($row);
            $faltantes = array_diff($this->encabezadosEsperados, $columnasArchivo);

            if (count($faltantes) > 0) {
                $this->errores[] = "El archivo no cumple con la estructura esperada. Faltan las columnas: " . implode(', ', $faltantes);
                return null;
            }
        }

        $folioExcel = isset($row['folio']) ? trim((string) $row['folio']) : null;

        // Validar folio vacío
        if (!$folioExcel) {
            $this->errores[] = "Fila sin folio detectada, no será importada.";
            return null;
        }

        // Validar duplicados
        if (DocumentoFinanciero::where('folio', $folioExcel)->exists()) {
            $this->duplicados[] = $folioExcel;
            return null;
        }

        // Buscar cobranza asociada
        $cobranza = Cobranza::where('rut_cliente', $row['rut_cliente'] ?? null)->first();
        if (!$cobranza) {
            $this->sinCobranza[] = [
                'razon_social' => $row['razon_social'],
                'rut_cliente' => $row['rut_cliente'],
                'folio' => $folioExcel,
            ];
            return null;
        }

        $fechaDocto = $this->transformDate($row['fecha_docto']);
        $fechaVencimiento = $this->calcularFechaVencimiento(
            Carbon::parse($fechaDocto)->toDateString(),
            $cobranza?->creditos
        );

        $estadoInicial = $this->definirEstadoInicial($fechaVencimiento);

        // 🔹 Verificar si es una Nota de Crédito (tipo 61)
        if ((int)($row['tipo_doc'] ?? 0) === 61) {

            $tipoReferencia = $row['tipo_docto_referencia'] ?? null;
            $folioReferencia = $row['folio_docto_referencia'] ?? null;

            if ($tipoReferencia && $folioReferencia) {
                $factura = DocumentoFinanciero::where('tipo_doc', $tipoReferencia)
                    ->where('folio', $folioReferencia)
                    ->first();

                // Crear la nota de crédito con vínculo (si existe la factura)
                $documento = new DocumentoFinanciero([
                    'folio' => $folioExcel,
                    'nro' => $row['nro'],
                    'tipo_doc' => $row['tipo_doc'],
                    'tipo_venta' => $row['tipo_venta'],
                    'rut_cliente' => $row['rut_cliente'],
                    'razon_social' => $row['razon_social'],
                    'fecha_docto' => $fechaDocto,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total' => $this->cleanNumber($row['monto_total']),
                    'empresa_id' => $this->empresaId,
                    'cobranza_id' => $cobranza?->id,
                    'status_original' => $estadoInicial,
                    'status' => $estadoInicial,
                    'referencia_id' => $factura?->id, // 👈 vínculo a la factura si existe
                ]);

                $documento->save();

                if ($factura) {
                    $this->notasCredito[] = "Nota de crédito folio {$row['folio']} vinculada correctamente a la factura {$factura->folio}.";
                } else {
                    $this->notasCredito[] = "⚠️ No se encontró la factura referenciada ({$tipoReferencia} - Folio {$folioReferencia}) para la nota de crédito {$row['folio']}.";
                }

                return null; // Evitar procesar como factura normal
            }
        }

        // 🧩 AQUÍ VIENE EL AJUSTE CLAVE (para facturas normales)
        $documento = new DocumentoFinanciero([
            'folio' => $folioExcel,
            'nro' => $row['nro'],
            'tipo_doc' => $row['tipo_doc'],
            'tipo_venta' => $row['tipo_venta'],
            'rut_cliente' => $row['rut_cliente'],
            'razon_social' => $row['razon_social'],

            // ✅ Conversión de fechas
            'fecha_docto' => $this->transformDate($row['fecha_docto']),
            'fecha_recepcion' => $this->transformDate($row['fecha_recepcion']),
            'fecha_acuse_recibo' => $this->transformDate($row['fecha_acuse_recibo']),
            'fecha_reclamo' => $this->transformDate($row['fecha_reclamo']),

            // 🔹 Cálculo de fecha de vencimiento
            'fecha_vencimiento' => $this->calcularFechaVencimiento(
                Carbon::parse($this->transformDate($row['fecha_docto']))->toDateString(),
                $cobranza?->creditos
            ),

            // ✅ Montos normalizados
            'monto_exento' => $this->cleanNumber($row['monto_exento']),
            'monto_neto' => $this->cleanNumber($row['monto_neto']),
            'monto_iva' => $this->cleanNumber($row['monto_iva']),
            'monto_total' => $this->cleanNumber($row['monto_total']),

            'iva_retenido_total' => $row['iva_retenido_total'],
            'iva_retenido_parcial' => $row['iva_retenido_parcial'],
            'iva_no_retenido' => $row['iva_no_retenido'],
            'iva_propio' => $row['iva_propio'],
            'iva_terceros' => $row['iva_terceros'],
            'rut_emisor_liquid_factura' => $row['rut_emisor_liquid_factura'],
            'neto_comision_liquid_factura' => $row['neto_comision_liquid_factura'],
            'exento_comision_liquid_factura' => $row['exento_comision_liquid_factura'],
            'iva_comision_liquid_factura' => $row['iva_comision_liquid_factura'],
            'iva_fuera_de_plazo' => $row['iva_fuera_de_plazo'],
            'tipo_docto_referencia' => $row['tipo_docto_referencia'],
            'folio_docto_referencia' => $row['folio_docto_referencia'],
            'num_ident_receptor_extranjero' => $row['num_ident_receptor_extranjero'],
            'nacionalidad_receptor_extranjero' => $row['nacionalidad_receptor_extranjero'],
            'credito_empresa_constructora' => $row['credito_empresa_constructora'],
            'impto_zona_franca_ley_18211' => $row['impto_zona_franca_ley_18211'],
            'garantia_dep_envases' => $row['garantia_dep_envases'],
            'indicador_venta_sin_costo' => $row['indicador_venta_sin_costo'],
            'indicador_servicio_periodico' => $row['indicador_servicio_periodico'],
            'monto_no_facturable' => $row['monto_no_facturable'],
            'total_monto_periodo' => $row['total_monto_periodo'],
            'venta_pasajes_transporte_nacional' => $row['venta_pasajes_transporte_nacional'],
            'venta_pasajes_transporte_internacional' => $row['venta_pasajes_transporte_internacional'],
            'numero_interno' => $row['numero_interno'],
            'codigo_sucursal' => $row['codigo_sucursal'],
            'nce_nde_sobre_fact_compra' => $row['nce_o_nde_sobre_fact_de_compra'],
            'codigo_otro_imp' => $row['codigo_otro_imp'],
            'valor_otro_imp' => $row['valor_otro_imp'],
            'tasa_otro_imp' => $row['tasa_otro_imp'],
            'cobranza_id' => $cobranza?->id,
            'empresa_id' => $this->empresaId,

            // 🧩 Guardamos ambos estados
            'status_original' => $estadoInicial,
            'status' => $estadoInicial,
        ]);

        // Registrar como importado solo después de todo el proceso
        $this->importados[] = $folioExcel;

        return $documento;
    }




    public function onError(Throwable $e)
    {
        $this->errores[] = $e->getMessage();
    }

    private function transformDate($value)
    {
        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
        }

        $formatos = [
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'Y-m-d H:i:s',
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
        ];

        foreach ($formatos as $formato) {
            try {
                return Carbon::createFromFormat($formato, trim($value))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // continúa
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanNumber($value)
    {
        if (!$value) return 0;
        $normalized = preg_replace('/[^\d]/', '', $value);
        return (int) $normalized;
    }

    private function calcularFechaVencimiento($fechaDocto, $creditos)
    {
        if (!$fechaDocto || !$creditos) {
            return null;
        }

        try {
            return Carbon::parse($fechaDocto)->addDays((int) $creditos)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function definirEstadoInicial($fechaVencimiento)
    {
        if (!$fechaVencimiento) {
            return null;
        }

        return Carbon::parse($fechaVencimiento)->isPast() ? 'Vencido' : 'Al día';
    }
}
