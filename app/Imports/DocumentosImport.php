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
    
    public function model(array $row)
    {

        // 🔹 Evitar procesar filas completamente vacías
        if (empty(array_filter($row))) {
            return null;
        }

        $folioExcel = isset($row['folio']) ? trim((string) $row['folio']) : null;

        // Validar folio vacío
        if (!$folioExcel) {
            $this->errores[] = "Fila sin folio detectada, no será importada.";
            return null;
        }

        // Validar duplicados
        if (DocumentoFinanciero::where('folio', $folioExcel)->exists()) {
            $this->errores[] = "El folio {$folioExcel} ya existe y no será importado.";
            return null;
        }

        
        $cobranza = \App\Models\Cobranza::where('rut_cliente', $row['rut_cliente'] ?? null)->first();

        return new DocumentoFinanciero([
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
            ]
        );
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

        // ✅ Caso 1: Serial de Excel (numérico)
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
        }

        // ✅ Caso 2: Texto con distintos formatos conocidos
        $formatos = [
            'd/m/Y H:i:s', // Ej: 15/09/2025 13:45:00
            'd-m-Y H:i:s', // Variante con guiones
            'Y-m-d H:i:s', // Formato estándar ISO con hora
            'd/m/Y',       // Ej: 15/09/2025
            'd-m-Y',       // Variante con guiones
            'Y-m-d',       // Formato estándar ISO sin hora
        ];

        foreach ($formatos as $formato) {
            try {
                return Carbon::createFromFormat($formato, trim($value))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // sigue probando con el siguiente formato
            }
        }

        // ✅ Caso 3: Último recurso → dejar que Carbon intente adivinar
        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null; // si no se puede parsear de ninguna forma
        }
    }



    private function cleanNumber($value)
    {
        if (!$value) return 0;

        // Quitar puntos, comas, espacios
        $normalized = preg_replace('/[^\d]/', '', $value);

        return (int) $normalized;
    }
}
