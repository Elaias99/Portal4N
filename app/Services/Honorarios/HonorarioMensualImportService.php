<?php

namespace App\Services\Honorarios;

use App\Models\CobranzaCompra;
use App\Models\Empresa;
use App\Services\Sii\HonorarioMensualRecParser;
use App\Services\Sii\HonorarioMensualTerceroRecParser;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class HonorarioMensualImportService
{
    public function execute(UploadedFile $archivo): array
    {
        $contenido = mb_convert_encoding(
            file_get_contents($archivo->getRealPath()),
            'UTF-8',
            ['ISO-8859-1', 'Windows-1252', 'UTF-8']
        );

        $esBoletaTerceros =
            Str::contains($contenido, 'Total Boletas') &&
            Str::contains($contenido, 'Receptor') &&
            (
                Str::contains($contenido, 'Emisi&oacute;n') ||
                Str::contains($contenido, 'Emisión')
            );

        $parser = $esBoletaTerceros
            ? new HonorarioMensualTerceroRecParser($archivo)
            : new HonorarioMensualRecParser($archivo);

        $preview = $parser->parse();

        if (empty($preview['tipo_boleta'])) {
            $preview['tipo_boleta'] = 'Boleta Honorario';
        }

        $primerRegistro = collect($preview['registros'])->first();

        $preview['meta']['rut_contribuyente'] = $preview['meta']['rut_contribuyente']
            ?? $primerRegistro['rut_contribuyente']
            ?? null;

        $preview['meta']['razon_social'] = $preview['meta']['razon_social']
            ?? $primerRegistro['razon_social']
            ?? null;

        if (
            empty($preview['meta']['anio']) ||
            empty($preview['meta']['mes'])
        ) {
            $fechaMeta = $primerRegistro['fecha_emision'] ?? null;

            if ($fechaMeta) {
                $fechaCarbon = Carbon::parse($fechaMeta);

                $preview['meta']['anio'] = $preview['meta']['anio'] ?? $fechaCarbon->year;
                $preview['meta']['mes']  = $preview['meta']['mes'] ?? $fechaCarbon->month;
            }
        }

        if (empty($preview['meta']['rut_contribuyente'])) {
            abort(422, 'No fue posible determinar el RUT contribuyente del archivo.');
        }

        if (empty($preview['meta']['razon_social'])) {
            abort(422, 'No fue posible determinar la razón social del archivo.');
        }

        if (empty($preview['meta']['anio']) || empty($preview['meta']['mes'])) {
            abort(422, 'No fue posible determinar el período del archivo.');
        }

        $rutArchivo = $preview['meta']['rut_contribuyente'];

        $rutLimpio = strtoupper(
            preg_replace('/[^0-9K]/', '', $rutArchivo)
        );

        $cuerpo = substr($rutLimpio, 0, -1);
        $dv     = substr($rutLimpio, -1);

        $rutFormateado = number_format($cuerpo, 0, '', '.') . '-' . $dv;

        $empresa = Empresa::where('rut', $rutFormateado)->first();

        if (!$empresa) {
            abort(422, 'Empresa no encontrada para el RUT informado por el SII.');
        }

        $preview['empresa'] = [
            'id'     => $empresa->id,
            'nombre' => $empresa->Nombre,
            'rut'    => $empresa->rut,
        ];

        $rutsEmisores = collect($preview['registros'])
            ->pluck('rut_emisor')
            ->filter()
            ->unique()
            ->values();

        $rutsExistentes = CobranzaCompra::whereIn('rut_cliente', $rutsEmisores)
            ->pluck('rut_cliente')
            ->toArray();

        $proveedoresFaltantes = $rutsEmisores
            ->reject(fn ($rut) => in_array($rut, $rutsExistentes))
            ->values();

        $preview['proveedores_faltantes'] = $proveedoresFaltantes
            ->map(function ($rut) use ($preview) {
                $registro = collect($preview['registros'])
                    ->firstWhere('rut_emisor', $rut);

                return [
                    'rut_emisor' => $rut,
                    'razon_social_emisor' => $registro['razon_social_emisor'] ?? null,
                ];
            })
            ->all();

        $totales = [
            'bruto'    => 0,
            'retenido' => 0,
            'pagado'   => 0,
        ];

        foreach ($preview['registros'] as $fila) {
            $estadoFila = mb_strtoupper(trim((string) ($fila['estado'] ?? '')));

            if (!in_array($estadoFila, ['ANULADA', 'NULA'], true)) {
                $totales['bruto']    += (int) ($fila['monto_bruto'] ?? 0);
                $totales['retenido'] += (int) ($fila['monto_retenido'] ?? 0);
                $totales['pagado']   += (int) ($fila['monto_pagado'] ?? 0);
            }
        }

        $preview['totales'] = $totales;

        return $preview;
    }
}