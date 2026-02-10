<?php

namespace App\Imports;

use App\Models\DocumentoCompra;
use App\Models\Cobranza;
use App\Models\CobranzaCompra;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class ComprasImport implements ToModel, WithHeadingRow
{
    protected $empresaId;

    //  Arrays auxiliares para trazabilidad
    public $duplicados = [];
    public $importados = [];
    public $sinCobranza = [];
    public $sugerenciasNotas = [];


    public $nuevos = 0;

    public function __construct($empresaId = null)
    {
        $this->empresaId = $empresaId;

        // Inicialización explícita
        $this->duplicados = [];
        $this->importados = [];
        $this->sinCobranza = [];
        
    }

    public function model(array $row)
    {
    
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        //  Normalizar encabezados
        $row = collect($row)->mapWithKeys(function ($value, $key) {
            $normalizedKey = str_replace(
                [' ', '.', 'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
                ['_', '', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'],
                mb_strtolower(trim($key))
            );
            return [$normalizedKey => $value];
        })->toArray();



        //  Evitar procesar filas completamente vacías
        if (empty(array_filter($row))) {
            return null;
        }

        $folio = $row['folio'] ?? null;

        //Validar folio vacío
        if (!$folio) {
            return null;
        }

        //Evitar procesar duplicados tanto en base de datos como dentro del mismo archivo
        static $foliosProcesados = []; // memoria temporal por archivo

        $folio = trim((string)($row['folio'] ?? ''));
        $rutProveedor = trim((string)($row['rut_proveedor'] ?? ''));
        $tipoDoc = (int)($row['tipo_doc'] ?? 0);

        //Revisar si ya se procesó en este mismo archivo
        $claveUnica = "{$this->empresaId}-{$tipoDoc}-{$rutProveedor}-{$folio}";

        if (in_array($claveUnica, $foliosProcesados, true)) {
            // Duplicado dentro del mismo archivo Excel
            $this->duplicados[] = $folio;
            return null;
        }

        $foliosProcesados[] = $claveUnica;

        //Revisar si ya existe en la base de datos
        if (
            \App\Models\DocumentoCompra::where('empresa_id', $this->empresaId)
                ->where('tipo_documento_id', $tipoDoc)
                ->where('rut_proveedor', $rutProveedor)
                ->where('folio', $folio)
                ->exists()
        ) {
            // Duplicado ya guardado en la tabla
            $this->duplicados[] = $folio;
            return null;
        }


        //Crear registro si no está duplicado
        $this->importados[] = $folio;
        $this->nuevos++;

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        //  Buscar cobranza asociada (por RUT o razón social)


// ...

    //Buscar cobranza asociada (por RUT del proveedor)
    $cobranza = CobranzaCompra::where('rut_cliente', $row['rut_proveedor'] ?? null)->first();



    //Determinar tipo de documento
    $tipoDocumento = \App\Models\TipoDocumento::find((int) $row['tipo_doc']);

    if (!$cobranza) {


        // Evitar duplicados por RUT
        $yaRegistrado = collect($this->sinCobranza)
            ->contains(fn($item) => $item['rut_proveedor'] === ($row['rut_proveedor'] ?? null));

        if (!$yaRegistrado) {
            $this->sinCobranza[] = [
                'razon_social'   => $row['razon_social'] ?? '(Sin nombre)',
                'rut_proveedor'  => $row['rut_proveedor'] ?? '(Sin RUT)',
                'folio'          => $row['folio'] ?? null,
                'fecha_docto'    => $row['fecha_docto'] ?? now(),
                'monto'          => $row['monto_total'] ?? 0,
            ];
        }

        //Crear el documento con cobranza_id = null (para ser reprocesado luego)
        \App\Models\DocumentoCompra::updateOrCreate(


            


            ['folio' => $row['folio']],
            [
                'empresa_id'        => $this->empresaId ?? null,
                'tipo_documento_id' => $tipoDocumento?->id,
                'nro'               => $row['nro'] ?? null,
                // 'tipo_doc'          => $row['tipo_doc'] ?? null,
                'tipo_compra'       => $row['tipo_compra'] ?? null,
                'rut_proveedor'     => $row['rut_proveedor'],
                'razon_social'      => $row['razon_social'],

                'fecha_docto'       => $this->transformDate($row['fecha_docto']),
                'fecha_recepcion'   => $this->transformDate($row['fecha_recepcion']),
                'fecha_acuse'       => $this->transformDate($row['fecha_acuse']),

                'monto_exento'      => $row['monto_exento'] ?? 0,
                'monto_neto'        => $row['monto_neto'] ?? 0,
                'monto_iva_recuperable' => $row['monto_iva_recuperable'] ?? 0,
                'monto_iva_no_recuperable' => $row['monto_iva_no_recuperable'] ?? 0,
                'codigo_iva_no_rec' => $row['codigo_iva_no_rec'] ?? null,
                'monto_total'       => $row['monto_total'] ?? 0,

                'monto_neto_activo_fijo' => $row['monto_neto_activo_fijo'] ?? 0,
                'iva_activo_fijo'   => $row['iva_activo_fijo'] ?? 0,
                'iva_uso_comun'     => $row['iva_uso_comun'] ?? 0,
                'impto_sin_derecho_credito' => $row['impto_sin_derecho_credito'] ?? 0,
                'iva_no_retenido'   => $row['iva_no_retenido'] ?? 0,
                'tabacos_puros'     => $row['tabacos_puros'] ?? 0,
                'tabacos_cigarrillos' => $row['tabacos_cigarrillos'] ?? 0,
                'tabacos_elaborados' => $row['tabacos_elaborados'] ?? 0,
                'nce_nde_sobre_fact_compra' => $row['nce_nde_sobre_fact_compra'] ?? null,
                'codigo_otro_impuesto' => $row['codigo_otro_impuesto'] ?? null,
                'valor_otro_impuesto'  => $row['valor_otro_impuesto'] ?? 0,
                'tasa_otro_impuesto'   => $row['tasa_otro_impuesto'] ?? 0,

                //Estado manual debe ser null
                'estado'            => null,




               
                'fecha_vencimiento' => null,
                //Estado automático real
                'status_original'   => 'Pendiente',






                
                // Saldo pendiente inicial
                'saldo_pendiente'   => $this->cleanNumber($row['monto_total'] ?? 0),

                'cobranza_compra_id' => null,
            ]
        );

        return null;
    }






        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        


        // Calcular fecha de vencimiento (si hay cobranza)
        $fechaDocto = $this->transformDate($row['fecha_docto'] ?? null);

        $fechaVencimiento = null;
        $statusOriginal = null;

        // Asegurar que la cobranza no rompa el flujo
        $cobranzaId = null;

        if ($cobranza) {
            $cobranzaId = $cobranza->id;

            // Créditos puede ser 0 → vencimiento inmediato
            $creditos = (int) ($cobranza->creditos ?? 0);

            if ($fechaDocto) {
                $fechaVencimiento = \Carbon\Carbon::parse($fechaDocto)
                    ->addDays($creditos)
                    ->format('Y-m-d');

                $statusOriginal = \Carbon\Carbon::parse($fechaVencimiento)->isPast()
                    ? 'Vencido'
                    : 'Al día';
            }
        }




        //Ahora ya puedes usar las claves igual que en DocumentosImport
        // (lo que tú ya tenías está correcto)
        $documento = new DocumentoCompra([
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
            'impto_sin_derecho_a_credito' => $this->cleanNumber($row['impto_sin_derecho_a_credito'] ?? 0),
            'iva_no_retenido' => $this->cleanNumber($row['iva_no_retenido'] ?? 0),
            'tabacos_puros' => $this->cleanNumber($row['tabacos_puros'] ?? 0),
            'tabacos_cigarrillos' => $this->cleanNumber($row['tabacos_cigarrillos'] ?? 0),
            'tabacos_elaborados' => $this->cleanNumber($row['tabacos_elaborados'] ?? 0),
            'nce_nde_sobre_fact_compra' => $row['nce_o_nde_sobre_fact_de_compra'] ?? null,
            'codigo_otro_impuesto' => $row['codigo_otro_impuesto'] ?? null,
            'valor_otro_impuesto' => $this->cleanNumber($row['valor_otro_impuesto'] ?? 0),
            'tasa_otro_impuesto' => $row['tasa_otro_impuesto'] ?? null,
            'cobranza_compra_id' => $cobranzaId,
            'fecha_vencimiento' => $fechaVencimiento,
            'status_original' => $statusOriginal,
            'saldo_pendiente' => $this->cleanNumber($row['monto_total'] ?? 0),
        ]);

        $documento->save();

        $this->importados[] = $row['folio'] ?? 'sin folio';

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
