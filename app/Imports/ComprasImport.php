<?php

namespace App\Imports;

use App\Models\DocumentoCompra;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class ComprasImport implements ToModel, WithHeadingRow
{
    protected $empresaId;

    // 🔹 Arrays auxiliares para trazabilidad
    public $duplicados = [];
    public $importados = [];

    public $nuevos = 0;

    public function __construct($empresaId = null)
    {
        $this->empresaId = $empresaId;

        // Inicialización explícita
        $this->duplicados = [];
        $this->importados = [];
        
    }

    public function model(array $row)
    {
    
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        // 🔸 Normalizar encabezados
        $row = collect($row)->mapWithKeys(function ($value, $key) {
            $normalizedKey = str_replace(
                [' ', '.', 'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
                ['_', '', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'],
                mb_strtolower(trim($key))
            );
            return [$normalizedKey => $value];
        })->toArray();



        // 🔹 Evitar procesar filas completamente vacías
        if (empty(array_filter($row))) {
            return null;
        }

        $folio = $row['folio'] ?? null;

        // ⚠️ Validar folio vacío
        if (!$folio) {
            return null;
        }

        // ⚠️ Validar duplicados (folio + empresa)
        if (DocumentoCompra::where('folio', $folio)
            ->where('empresa_id', $this->empresaId)
            ->exists()) {

            $this->duplicados[] = $folio;
            return null;
        }

        // ✅ Crear registro si no está duplicado
        $this->importados[] = $folio;
        $this->nuevos++;

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        // 🔍 Buscar cobranza asociada (por RUT o razón social)
        $cobranza = \App\Models\Cobranza::where('rut_cliente', $row['rut_proveedor'] ?? null)
            ->orWhere('razon_social', 'LIKE', "%{$row['razon_social']}%")
            ->first();

        
        if (!$cobranza) {
        return null;
    }


        // 📅 Calcular fecha de vencimiento (si hay cobranza)
        $fechaDocto = $this->transformDate($row['fecha_docto'] ?? null);

        $fechaVencimiento = null;
        $statusOriginal = null;

        // Asegurar que la cobranza no rompa el flujo
        $cobranzaId = null;
        if ($cobranza) {
            $cobranzaId = $cobranza->id;

            if ($fechaDocto && $cobranza->creditos) {
                $fechaVencimiento = \Carbon\Carbon::parse($fechaDocto)
                    ->addDays((int) $cobranza->creditos)
                    ->format('Y-m-d');

                $statusOriginal = \Carbon\Carbon::parse($fechaVencimiento)->isPast()
                    ? 'Vencido'
                    : 'Al día';
            }
        }



        // 🔹 Ahora ya puedes usar las claves igual que en DocumentosImport
        // (lo que tú ya tenías está correcto)
        return new DocumentoCompra([
            'empresa_id' => $this->empresaId,
            'tipo_documento_id' => $row['tipo_doc'] ?? null,
            'nro' => $row['nro'] ?? null,
            'tipo_compra' => $row['tipo_compra'] ?? null,
            'rut_proveedor' => $row['rut_proveedor'] ?? null,
            'razon_social' => $row['razon_social'] ?? null,
            'folio' => $folio,
            'fecha_docto' => $this->transformDate($row['fecha_docto'] ?? null),
            'fecha_recepcion' => $this->transformDate($row['fecha_recepcion'] ?? null),
            'fecha_acuse' => $this->transformDate($row['fecha_acuse'] ?? null),
            'monto_exento' => $this->cleanNumber($row['monto_exento'] ?? 0),
            'monto_neto' => $this->cleanNumber($row['monto_neto'] ?? 0),
            'monto_iva_recuperable' => $this->cleanNumber($row['monto_iva_recuperable'] ?? 0),
            'monto_iva_no_recuperable' => $this->cleanNumber($row['monto_iva_no_recuperable'] ?? 0),
            'codigo_iva_no_rec' => $row['codigo_iva_no_rec'] ?? null,
            'monto_total' => $this->cleanNumber($row['monto_total'] ?? 0),
            'monto_neto_activo_fijo' => $this->cleanNumber($row['monto_neto_activo_fijo'] ?? 0),
            'iva_activo_fijo' => $this->cleanNumber($row['iva_activo_fijo'] ?? 0),
            'iva_uso_comun' => $this->cleanNumber($row['iva_uso_comun'] ?? 0),
            'impto_sin_derecho_credito' => $this->cleanNumber($row['impto_sin_derecho_a_credito'] ?? 0),
            'iva_no_retenido' => $this->cleanNumber($row['iva_no_retenido'] ?? 0),
            'tabacos_puros' => $this->cleanNumber($row['tabacos_puros'] ?? 0),
            'tabacos_cigarrillos' => $this->cleanNumber($row['tabacos_cigarrillos'] ?? 0),
            'tabacos_elaborados' => $this->cleanNumber($row['tabacos_elaborados'] ?? 0),
            'nce_nde_sobre_fact_compra' => $row['nce_o_nde_sobre_fact_de_compra'] ?? null,
            'codigo_otro_impuesto' => $row['codigo_otro_impuesto'] ?? null,
            'valor_otro_impuesto' => $this->cleanNumber($row['valor_otro_impuesto'] ?? 0),
            'tasa_otro_impuesto' => $row['tasa_otro_impuesto'] ?? null,


            'cobranza_id' => $cobranzaId,

            'fecha_vencimiento' => $fechaVencimiento,
            'status_original' => $statusOriginal,
        ]);


        $this->importados[] = $row['folio'] ?? 'sin folio'; // ✅ Solo aquí cuenta el guardado

        return $documento;






    }


    // --- helpers ---
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
}
