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

        // 🔄 Re-inicialización explícita
        $this->errores = [];
        $this->importados = [];
        $this->duplicados = [];
        $this->sinCobranza = [];
        $this->notasCredito = [];
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

        $tipoDocumento = \App\Models\TipoDocumento::find((int) $row['tipo_doc']);

        if (!$cobranza) {
            // Evitar duplicados por RUT
            $yaRegistrado = collect($this->sinCobranza)
                ->contains(fn($item) => $item['rut_cliente'] === $row['rut_cliente']);

            if (!$yaRegistrado) {
                $this->sinCobranza[] = [
                    'razon_social' => $row['razon_social'],
                    'rut_cliente' => $row['rut_cliente'],
                    'folio' => $folioExcel,
                    'fecha_docto' => $row['fecha_docto'] ?? now(),
                    'monto' => $row['monto_total'] ?? 0,
                ];
            }

            // 🧩 Crear el documento con cobranza_id = null (para ser reprocesado luego)
            \App\Models\DocumentoFinanciero::updateOrCreate(
                ['folio' => $folioExcel],
                [
                    'empresa_id'              => $this->empresaId ?? null,
                    'tipo_documento_id'       => $tipoDocumento?->id ?? null,
                    'nro'                     => $row['nro'] ?? null,
                  
                    'tipo_venta'              => $row['tipo_venta'] ?? null,
                    'rut_cliente'             => $row['rut_cliente'] ?? null,
                    'razon_social'            => $row['razon_social'] ?? null,
                    'fecha_docto'             => $this->transformDate($row['fecha_docto']),
                    'fecha_recepcion'         => $this->transformDate($row['fecha_recepcion']),
                    'fecha_acuse_recibo'      => $this->transformDate($row['fecha_acuse_recibo']),
                    'fecha_reclamo'           => $this->transformDate($row['fecha_reclamo']),
                    'monto_exento'            => $this->cleanNumber($row['monto_exento']),
                    'monto_neto'              => $this->cleanNumber($row['monto_neto']),
                    'monto_iva'               => $this->cleanNumber($row['monto_iva']),
                    'monto_total'             => $this->cleanNumber($row['monto_total']),
                    'saldo_pendiente'         => $this->cleanNumber($row['monto_total']),
                    'iva_retenido_total'      => $this->cleanNumber($row['iva_retenido_total']),
                    'iva_retenido_parcial'    => $this->cleanNumber($row['iva_retenido_parcial']),
                    'iva_no_retenido'         => $this->cleanNumber($row['iva_no_retenido']),
                    'iva_propio'              => $this->cleanNumber($row['iva_propio']),
                    'iva_terceros'            => $this->cleanNumber($row['iva_terceros']),
                    'rut_emisor_liquid_factura'      => $row['rut_emisor_liquid_factura'] ?? null,
                    'neto_comision_liquid_factura'   => $this->cleanNumber($row['neto_comision_liquid_factura']),
                    'exento_comision_liquid_factura' => $this->cleanNumber($row['exento_comision_liquid_factura']),
                    'iva_comision_liquid_factura'    => $this->cleanNumber($row['iva_comision_liquid_factura']),
                    'iva_fuera_de_plazo'             => $this->cleanNumber($row['iva_fuera_de_plazo']),
                    'tipo_docto_referencia'          => $row['tipo_docto_referencia'] ?? null,
                    'folio_docto_referencia'         => $row['folio_docto_referencia'] ?? null,
                    'num_ident_receptor_extranjero'  => $row['num_ident_receptor_extranjero'] ?? null,
                    'nacionalidad_receptor_extranjero' => $row['nacionalidad_receptor_extranjero'] ?? null,
                    'credito_empresa_constructora'   => $this->cleanNumber($row['credito_empresa_constructora']),
                    'impto_zona_franca_ley_18211'    => $this->cleanNumber($row['impto_zona_franca_ley_18211']),
                    'garantia_dep_envases'           => $this->cleanNumber($row['garantia_dep_envases']),
                    'indicador_venta_sin_costo'      => $row['indicador_venta_sin_costo'] ?? null,
                    'indicador_servicio_periodico'   => $row['indicador_servicio_periodico'] ?? null,
                    'monto_no_facturable'            => $this->cleanNumber($row['monto_no_facturable']),
                    'total_monto_periodo'            => $this->cleanNumber($row['total_monto_periodo']),
                    'venta_pasajes_transporte_nacional'      => $this->cleanNumber($row['venta_pasajes_transporte_nacional']),
                    'venta_pasajes_transporte_internacional' => $this->cleanNumber($row['venta_pasajes_transporte_internacional']),
                    'numero_interno'                 => $row['numero_interno'] ?? null,
                    'codigo_sucursal'                => $row['codigo_sucursal'] ?? null,
                    'nce_nde_sobre_fact_compra'      => $row['nce_o_nde_sobre_fact_de_compra'] ?? null,
                    'codigo_otro_imp'                => $row['codigo_otro_imp'] ?? null,
                    'valor_otro_imp'                 => $this->cleanNumber($row['valor_otro_imp']),
                    'tasa_otro_imp'                  => $this->cleanNumber($row['tasa_otro_imp']),
                    'estado'                         => 'Pendiente',
                    'status'                         => 'Pendiente',
                    'status_original'                => 'Pendiente',
                    'cobranza_id'                    => null,
                    
                    'fecha_vencimiento' => $cobranza
                        ? $this->calcularFechaVencimiento(
                            Carbon::parse($this->transformDate($row['fecha_docto']))->toDateString(),
                            $cobranza->creditos
                        )
                        : null,


                ]
            );


            return null;
        }



        $fechaDocto = $this->transformDate($row['fecha_docto']);
        $fechaVencimiento = $this->calcularFechaVencimiento(
            Carbon::parse($fechaDocto)->toDateString(),
            $cobranza?->creditos
        );

        $estadoInicial = $this->definirEstadoInicial($fechaVencimiento);

        // 🔹 Verificar si es una Nota de Crédito (tipo 61)
        // 🔹 Verificar si es una Nota de Crédito (tipo 61)
        if ((int)($row['tipo_doc'] ?? 0) === 61) {

            // 🧩 Detección flexible para diferentes encabezados
            $tipoReferencia = $row['tipo_docto_referencia']
                ?? $row['tipo_doc_ref']
                ?? $row['tipo_doc_referencia']
                ?? $row['tipo_documento_referencia']
                ?? $row['tpodocref']
                ?? null;

            $folioReferencia = $row['folio_docto_referencia']
                ?? $row['folio_doc_ref']
                ?? $row['folio_referencia']
                ?? $row['foliodocref']
                ?? null;

            // 🔍 Buscar la factura referenciada
            $factura = null;
            if ($tipoReferencia && $folioReferencia) {
                $factura = DocumentoFinanciero::where('tipo_documento_id', $tipoReferencia)
                    ->where('folio', $folioReferencia)
                    ->first();
            }

            // 🧾 Crear la nota de crédito
            $documento = new DocumentoFinanciero([
                'folio' => $folioExcel,
                'nro' => $row['nro'],
                'tipo_documento_id' => $tipoDocumento?->id,
                'tipo_venta' => $row['tipo_venta'],
                'rut_cliente' => $row['rut_cliente'],
                'razon_social' => $row['razon_social'],
                'fecha_docto' => $this->transformDate($row['fecha_docto']),
                'fecha_recepcion' => $this->transformDate($row['fecha_recepcion']),
                'fecha_acuse_recibo' => $this->transformDate($row['fecha_acuse_recibo']),
                'fecha_reclamo' => $this->transformDate($row['fecha_reclamo']),
                'fecha_vencimiento' => $this->calcularFechaVencimiento(
                    Carbon::parse($this->transformDate($row['fecha_docto']))->toDateString(),
                    $cobranza?->creditos
                ),
                'monto_exento' => $this->cleanNumber($row['monto_exento']),
                'monto_neto' => $this->cleanNumber($row['monto_neto']),
                'monto_iva' => $this->cleanNumber($row['monto_iva']),
                'monto_total' => $this->cleanNumber($row['monto_total']),
                'saldo_pendiente' => 0,

                'empresa_id' => $this->empresaId,
                'cobranza_id' => $cobranza?->id,
                'status_original' => $estadoInicial,
                'status' => $estadoInicial,
                'tipo_docto_referencia' => $tipoReferencia,
                'folio_docto_referencia' => $folioReferencia,
                'referencia_id' => $factura?->id,
            ]);

            $documento->save();

            // // 💬 Mensaje informativo
            // $this->notasCredito[] = $factura
            //     ? "✅ Nota de crédito folio {$row['folio']} vinculada correctamente a la factura {$factura->folio}."
            //     : "⚠️ Nota de crédito {$row['folio']} creada sin documento de referencia.";

            return $documento; // 👈 Retorna para que Maatwebsite la cuente como importada
        }




        // 🧩 AQUÍ VIENE EL AJUSTE CLAVE (para facturas normales)
        $documento = new DocumentoFinanciero([
            'folio' => $folioExcel,
            'nro' => $row['nro'],


            'tipo_documento_id' => $tipoDocumento?->id,



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
            'saldo_pendiente' => $this->cleanNumber($row['monto_total']),


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

    public function afterImport()
    {
        $this->notasCredito = [];

        // 1️⃣ Vincular las notas de crédito importadas en este archivo
        $notas = \App\Models\DocumentoFinanciero::where('tipo_documento_id', 61)
            ->whereIn('folio', $this->importados)
            ->get();

        foreach ($notas as $nota) {
            $this->vincularNotaCredito($nota);
        }

        // 2️⃣ Intentar vincular notas antiguas que quedaron huérfanas
        $notasPendientes = \App\Models\DocumentoFinanciero::where('tipo_documento_id', 61)
            ->whereNull('referencia_id')
            ->whereNotNull('folio_docto_referencia')
            ->get();

        foreach ($notasPendientes as $nota) {
            $this->vincularNotaCredito($nota, false);
        }
    }

    /**
     * 🔗 Vincula una nota de crédito con su factura referenciada si existe
     */
    private function vincularNotaCredito($nota, $esImportada = true)
    {
        $factura = \App\Models\DocumentoFinanciero::where('folio', $nota->folio_docto_referencia)
            ->where('tipo_documento_id', $nota->tipo_docto_referencia)
            ->first();

        if ($factura) {
            // 1️⃣ Vincular nota -> factura
            if (!$nota->referencia_id) {
                $nota->referencia_id = $factura->id;
                $nota->save();
            }



            // 2️⃣ Vincular factura -> nota (relación inversa)
            //    Esto asegura que la factura muestre "Referenciada por NC N°..."
        if (!$factura->referenciados()->where('id', $nota->id)->exists()) {
            $factura->referenciados()->save($nota);
        }

        $factura->refresh();

        $factura->recalcularSaldoPendiente();
        $factura->save();

        // 3️⃣ Mensaje informativo solo si fue importada en este archivo
        if ($esImportada) {
                $this->notasCredito[] = "✅ Nota de crédito folio {$nota->folio} vinculada correctamente a la factura {$factura->folio}.";
            }
        } else {
            if ($esImportada) {
                $this->notasCredito[] = "⚠️ Nota de crédito folio {$nota->folio} no pudo vincularse porque la factura referenciada aún no existe.";
            }
        }
    }


    




}
