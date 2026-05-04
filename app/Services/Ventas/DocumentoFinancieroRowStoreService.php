<?php

namespace App\Services\Ventas;

use App\Models\Cobranza;
use App\Models\DocumentoFinanciero;
use App\Models\TipoDocumento;
use App\Support\Ventas\DocumentoFinancieroImportState;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DocumentoFinancieroRowStoreService
{
    protected array $encabezadosEsperados = [
        'folio',
        'nro',
        'tipo_doc',
        'tipo_venta',
        'rut_cliente',
        'razon_social',
        'fecha_docto',
        'fecha_recepcion',
        'fecha_acuse_recibo',
        'fecha_reclamo',
        'monto_exento',
        'monto_neto',
        'monto_iva',
        'monto_total',
    ];

    public function handle(array $row, ?int $empresaId, DocumentoFinancieroImportState $state): ?DocumentoFinanciero
    {
        if ($this->filaVacia($row)) {
            return null;
        }

        if (!$state->estructuraVerificada) {
            $state->estructuraVerificada = true;

            $columnasArchivo = array_keys($row);
            $faltantes = array_diff($this->encabezadosEsperados, $columnasArchivo);

            if (count($faltantes) > 0) {
                $state->agregarError(
                    'El archivo no cumple con la estructura esperada. Faltan las columnas: ' . implode(', ', $faltantes)
                );
                return null;
            }
        }

        $folioExcel = trim((string) ($row['folio'] ?? ''));

        if ($folioExcel === '') {
            $state->agregarError('Fila sin folio detectada, no será importada.');
            return null;
        }

        $rutCliente = trim((string) ($row['rut_cliente'] ?? ''));
        $tipoDoc    = (int) ($row['tipo_doc'] ?? 0);

        $claveUnica = "{$empresaId}-{$tipoDoc}-{$rutCliente}-{$folioExcel}";

        if (in_array($claveUnica, $state->foliosProcesados, true)) {
            $state->agregarDuplicado($folioExcel);
            return null;
        }

        $state->foliosProcesados[] = $claveUnica;

        if (
            DocumentoFinanciero::where('empresa_id', $empresaId)
                ->where('tipo_documento_id', $tipoDoc)
                ->where('rut_cliente', $rutCliente)
                ->where('folio', $folioExcel)
                ->exists()
        ) {
            $state->agregarDuplicado($folioExcel);
            return null;
        }

        $cobranza = Cobranza::where('rut_cliente', $row['rut_cliente'] ?? null)->first();
        $tipoDocumento = TipoDocumento::find($tipoDoc);

        if (!$cobranza) {
            return $this->guardarDocumentoSinCobranza(
                row: $row,
                empresaId: $empresaId,
                tipoDocumento: $tipoDocumento,
                folioExcel: $folioExcel,
                state: $state,
            );
        }

        if ($tipoDoc === 61) {
            return $this->guardarNotaCredito(
                row: $row,
                empresaId: $empresaId,
                cobranza: $cobranza,
                tipoDocumento: $tipoDocumento,
                folioExcel: $folioExcel,
                state: $state,
            );
        }

        return $this->guardarDocumentoNormal(
            row: $row,
            empresaId: $empresaId,
            cobranza: $cobranza,
            tipoDocumento: $tipoDocumento,
            folioExcel: $folioExcel,
            state: $state,
        );
    }

    protected function guardarDocumentoSinCobranza(
        array $row,
        ?int $empresaId,
        ?TipoDocumento $tipoDocumento,
        string $folioExcel,
        DocumentoFinancieroImportState $state,
    ): ?DocumentoFinanciero {
        $state->agregarSinCobranza(
            rutCliente: $row['rut_cliente'] ?? null,
            razonSocial: $row['razon_social'] ?? null,
            folio: $folioExcel,
        );



        $rutCliente = trim((string) ($row['rut_cliente'] ?? ''));
        // Se preserva la misma conducta actual:
        // updateOrCreate usando solo folio como llave.
        DocumentoFinanciero::updateOrCreate(
            [    'empresa_id' => $empresaId,
                'tipo_documento_id' => $tipoDocumento?->id,
                'rut_cliente' => $rutCliente,
                'folio' => $folioExcel,],
            [
                'empresa_id'                    => $empresaId,
                'tipo_documento_id'             => $tipoDocumento?->id,
                'nro'                           => $row['nro'] ?? null,
                'tipo_venta'                    => $row['tipo_venta'] ?? null,
                'rut_cliente'                   => $row['rut_cliente'] ?? null,
                'razon_social'                  => $row['razon_social'] ?? null,
                'fecha_docto'                   => $this->transformDate($row['fecha_docto'] ?? null),
                'fecha_recepcion'               => $this->transformDate($row['fecha_recepcion'] ?? null),
                'fecha_acuse_recibo'            => $this->transformDate($row['fecha_acuse_recibo'] ?? null),
                'fecha_reclamo'                 => $this->transformDate($row['fecha_reclamo'] ?? null),
                'monto_exento'                  => $this->cleanNumber($row['monto_exento'] ?? null),
                'monto_neto'                    => $this->cleanNumber($row['monto_neto'] ?? null),
                'monto_iva'                     => $this->cleanNumber($row['monto_iva'] ?? null),
                'monto_total'                   => $this->cleanNumber($row['monto_total'] ?? null),
                'saldo_pendiente'               => ((int)($row['tipo_doc'] ?? 0) === 61)
                    ? 0
                    : $this->cleanNumber($row['monto_total'] ?? null),

                'iva_retenido_total'            => $this->cleanNumber($row['iva_retenido_total'] ?? null),
                'iva_retenido_parcial'          => $this->cleanNumber($row['iva_retenido_parcial'] ?? null),
                'iva_no_retenido'               => $this->cleanNumber($row['iva_no_retenido'] ?? null),
                'iva_propio'                    => $this->cleanNumber($row['iva_propio'] ?? null),
                'iva_terceros'                  => $this->cleanNumber($row['iva_terceros'] ?? null),
                'rut_emisor_liquid_factura'     => $row['rut_emisor_liquid_factura'] ?? null,
                'neto_comision_liquid_factura'  => $this->cleanNumber($row['neto_comision_liquid_factura'] ?? null),
                'exento_comision_liquid_factura'=> $this->cleanNumber($row['exento_comision_liquid_factura'] ?? null),
                'iva_comision_liquid_factura'   => $this->cleanNumber($row['iva_comision_liquid_factura'] ?? null),
                'iva_fuera_de_plazo'            => $this->cleanNumber($row['iva_fuera_de_plazo'] ?? null),
                'tipo_docto_referencia'         => $row['tipo_docto_referencia'] ?? null,
                'folio_docto_referencia'        => $row['folio_docto_referencia'] ?? null,
                'num_ident_receptor_extranjero' => $row['num_ident_receptor_extranjero'] ?? null,
                'nacionalidad_receptor_extranjero' => $row['nacionalidad_receptor_extranjero'] ?? null,
                'credito_empresa_constructora'  => $this->cleanNumber($row['credito_empresa_constructora'] ?? null),
                'impto_zona_franca_ley_18211'   => $this->cleanNumber($row['impto_zona_franca_ley_18211'] ?? null),
                'garantia_dep_envases'          => $this->cleanNumber($row['garantia_dep_envases'] ?? null),
                'indicador_venta_sin_costo'     => $row['indicador_venta_sin_costo'] ?? null,
                'indicador_servicio_periodico'  => $row['indicador_servicio_periodico'] ?? null,
                'monto_no_facturable'           => $this->cleanNumber($row['monto_no_facturable'] ?? null),
                'total_monto_periodo'           => $this->cleanNumber($row['total_monto_periodo'] ?? null),
                'venta_pasajes_transporte_nacional' => $this->cleanNumber($row['venta_pasajes_transporte_nacional'] ?? null),
                'venta_pasajes_transporte_internacional' => $this->cleanNumber($row['venta_pasajes_transporte_internacional'] ?? null),
                'numero_interno'                => $row['numero_interno'] ?? null,
                'codigo_sucursal'               => $row['codigo_sucursal'] ?? null,
                'nce_nde_sobre_fact_compra'     => $row['nce_o_nde_sobre_fact_de_compra'] ?? null,
                'codigo_otro_imp'               => $row['codigo_otro_imp'] ?? null,
                'valor_otro_imp'                => $this->cleanNumber($row['valor_otro_imp'] ?? null),
                'tasa_otro_imp'                 => $this->cleanNumber($row['tasa_otro_imp'] ?? null),
                'status'                        => null,
                'status_original'               => $this->definirEstadoInicial(
                    $this->calcularFechaVencimiento(
                        Carbon::parse($this->transformDate($row['fecha_docto'] ?? null))->toDateString(),
                        30
                    )
                ),
                'cobranza_id'                   => null,
                'fecha_vencimiento'             => null,
            ]
        );

        return null;
    }

    protected function guardarNotaCredito(
        array $row,
        ?int $empresaId,
        Cobranza $cobranza,
        ?TipoDocumento $tipoDocumento,
        string $folioExcel,
        DocumentoFinancieroImportState $state,
    ): DocumentoFinanciero {
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



        $factura = null;

        $rutCliente = trim((string) ($row['rut_cliente'] ?? ''));
        $folioReferencia = trim((string) $folioReferencia);

        if ($tipoReferencia && $folioReferencia !== '') {
            $factura = DocumentoFinanciero::where('empresa_id', $empresaId)
                ->where('rut_cliente', $rutCliente)
                ->where('tipo_documento_id', (int) $tipoReferencia)
                ->where('folio', $folioReferencia)
                ->first();
        }




        $fechaDocto = $this->transformDate($row['fecha_docto'] ?? null);
        $fechaVencimiento = $this->calcularFechaVencimiento(
            Carbon::parse($fechaDocto)->toDateString(),
            $cobranza->creditos
        );

        $estadoInicial = $this->definirEstadoInicial($fechaVencimiento);

        $documento = new DocumentoFinanciero([
            'folio'                   => $folioExcel,
            'nro'                     => $row['nro'] ?? null,
            'tipo_documento_id'       => $tipoDocumento?->id,
            'tipo_venta'              => $row['tipo_venta'] ?? null,
            'rut_cliente'             => $row['rut_cliente'] ?? null,
            'razon_social'            => $row['razon_social'] ?? null,
            'fecha_docto'             => $fechaDocto,
            'fecha_recepcion'         => $this->transformDate($row['fecha_recepcion'] ?? null),
            'fecha_acuse_recibo'      => $this->transformDate($row['fecha_acuse_recibo'] ?? null),
            'fecha_reclamo'           => $this->transformDate($row['fecha_reclamo'] ?? null),
            'fecha_vencimiento'       => $fechaVencimiento,
            'monto_exento'            => $this->cleanNumber($row['monto_exento'] ?? null),
            'monto_neto'              => $this->cleanNumber($row['monto_neto'] ?? null),
            'monto_iva'               => $this->cleanNumber($row['monto_iva'] ?? null),
            'monto_total'             => $this->cleanNumber($row['monto_total'] ?? null),
            'saldo_pendiente'         => 0,
            'empresa_id'              => $empresaId,
            'cobranza_id'             => $cobranza->id,
            'status_original'         => $estadoInicial,
            'status'                  => null,
            'tipo_docto_referencia'   => $tipoReferencia,
            'folio_docto_referencia'  => $folioReferencia,
            'referencia_id'           => $factura?->id,
        ]);

        $documento->save();

        // Ojo: aquí no agrego a importados para respetar 1:1 el comportamiento actual.
        return $documento;
    }

    protected function guardarDocumentoNormal(
        array $row,
        ?int $empresaId,
        Cobranza $cobranza,
        ?TipoDocumento $tipoDocumento,
        string $folioExcel,
        DocumentoFinancieroImportState $state,
    ): DocumentoFinanciero {
        $fechaDocto = $this->transformDate($row['fecha_docto'] ?? null);
        $fechaVencimiento = $this->calcularFechaVencimiento(
            Carbon::parse($fechaDocto)->toDateString(),
            $cobranza->creditos
        );

        $estadoInicial = $this->definirEstadoInicial($fechaVencimiento);

        $documento = new DocumentoFinanciero([
            'folio'                   => $folioExcel,
            'nro'                     => $row['nro'] ?? null,
            'tipo_documento_id'       => $tipoDocumento?->id,
            'tipo_venta'              => $row['tipo_venta'] ?? null,
            'rut_cliente'             => $row['rut_cliente'] ?? null,
            'razon_social'            => $row['razon_social'] ?? null,
            'fecha_docto'             => $fechaDocto,
            'fecha_recepcion'         => $this->transformDate($row['fecha_recepcion'] ?? null),
            'fecha_acuse_recibo'      => $this->transformDate($row['fecha_acuse_recibo'] ?? null),
            'fecha_reclamo'           => $this->transformDate($row['fecha_reclamo'] ?? null),
            'fecha_vencimiento'       => $fechaVencimiento,
            'monto_exento'            => $this->cleanNumber($row['monto_exento'] ?? null),
            'monto_neto'              => $this->cleanNumber($row['monto_neto'] ?? null),
            'monto_iva'               => $this->cleanNumber($row['monto_iva'] ?? null),
            'monto_total'             => $this->cleanNumber($row['monto_total'] ?? null),
            'saldo_pendiente'         => $this->cleanNumber($row['monto_total'] ?? null),
            'iva_retenido_total'      => $row['iva_retenido_total'] ?? null,
            'iva_retenido_parcial'    => $row['iva_retenido_parcial'] ?? null,
            'iva_no_retenido'         => $row['iva_no_retenido'] ?? null,
            'iva_propio'              => $row['iva_propio'] ?? null,
            'iva_terceros'            => $row['iva_terceros'] ?? null,
            'rut_emisor_liquid_factura' => $row['rut_emisor_liquid_factura'] ?? null,
            'neto_comision_liquid_factura' => $row['neto_comision_liquid_factura'] ?? null,
            'exento_comision_liquid_factura' => $row['exento_comision_liquid_factura'] ?? null,
            'iva_comision_liquid_factura' => $row['iva_comision_liquid_factura'] ?? null,
            'iva_fuera_de_plazo'      => $row['iva_fuera_de_plazo'] ?? null,
            'tipo_docto_referencia'   => $row['tipo_docto_referencia'] ?? null,
            'folio_docto_referencia'  => $row['folio_docto_referencia'] ?? null,
            'num_ident_receptor_extranjero' => $row['num_ident_receptor_extranjero'] ?? null,
            'nacionalidad_receptor_extranjero' => $row['nacionalidad_receptor_extranjero'] ?? null,
            'credito_empresa_constructora' => $row['credito_empresa_constructora'] ?? null,
            'impto_zona_franca_ley_18211' => $row['impto_zona_franca_ley_18211'] ?? null,
            'garantia_dep_envases'    => $row['garantia_dep_envases'] ?? null,
            'indicador_venta_sin_costo' => $row['indicador_venta_sin_costo'] ?? null,
            'indicador_servicio_periodico' => $row['indicador_servicio_periodico'] ?? null,
            'monto_no_facturable'     => $row['monto_no_facturable'] ?? null,
            'total_monto_periodo'     => $row['total_monto_periodo'] ?? null,
            'venta_pasajes_transporte_nacional' => $row['venta_pasajes_transporte_nacional'] ?? null,
            'venta_pasajes_transporte_internacional' => $row['venta_pasajes_transporte_internacional'] ?? null,
            'numero_interno'          => $row['numero_interno'] ?? null,
            'codigo_sucursal'         => $row['codigo_sucursal'] ?? null,
            'nce_nde_sobre_fact_compra' => $row['nce_o_nde_sobre_fact_de_compra'] ?? null,
            'codigo_otro_imp'         => $row['codigo_otro_imp'] ?? null,
            'valor_otro_imp'          => $row['valor_otro_imp'] ?? null,
            'tasa_otro_imp'           => $row['tasa_otro_imp'] ?? null,
            'cobranza_id'             => $cobranza->id,
            'empresa_id'              => $empresaId,
            'status_original'         => $estadoInicial,
            'status'                  => null,
        ]);

        $documento->save();

        $state->agregarImportado($folioExcel);

        return $documento;
    }

    protected function filaVacia(array $row): bool
    {
        return empty(array_filter($row, fn ($value) => $value !== null && $value !== ''));
    }

    protected function transformDate($value): ?string
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
                return Carbon::createFromFormat($formato, trim((string) $value))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function cleanNumber($value): int
    {
        if (!$value) {
            return 0;
        }

        $normalized = preg_replace('/[^\d]/', '', (string) $value);
        return (int) $normalized;
    }

    protected function calcularFechaVencimiento(?string $fechaDocto, $creditos): ?string
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

    protected function definirEstadoInicial(?string $fechaVencimiento): ?string
    {
        if (!$fechaVencimiento) {
            return null;
        }

        return Carbon::parse($fechaVencimiento)->isPast() ? 'Vencido' : 'Al día';
    }
}