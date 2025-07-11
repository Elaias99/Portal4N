<?php

namespace App\Exports;

use App\Models\Compra;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CompraExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    public function query()
    {
        return Compra::with([
            'empresa',
            'proveedor',
            'centroCosto',
            'tipoPago',
            'plazoPago',
            'formaPago',
            'user'
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($compra): array
    {
        return [
            $compra->empresa->Nombre ?? 'Sin Registro',
            $compra->proveedor->rut ?? 'Sin Registro',
            $compra->proveedor->razon_social ?? 'Sin Registro',
            $compra->centroCosto->nombre ?? 'Sin Registro',
            $compra->glosa,
            $compra->observacion,
            $compra->tipoPago->nombre ?? 'Sin Registro',
            $compra->plazoPago->nombre ?? 'Sin Registro',
            $compra->formaPago->nombre ?? 'Sin Registro',
            $compra->pago_total,
            optional($compra->fecha_vencimiento)->format('Y-m-d'),
            $compra->año,
            $compra->mes,
            optional($compra->fecha_documento)->format('Y-m-d'),
            $compra->numero_documento,
            $compra->oc,
            $compra->status,
            $compra->user->name ?? 'Sin Registro',
            $compra->archivo_oc,
            $compra->archivo_documento,
        ];
    }

    public function headings(): array
    {
        return [
            'empresa',
            'rut',
            'proveedor',
            'centro_costo',
            'glosa',
            'observacion',
            'tipo_de_documento',
            'plazo_pago',
            'forma_pago',
            'pago_total',
            'fecha_vencimiento',
            'ano',
            'mes',
            'fecha_documento',
            'numero_documento',
            'oc',
            'status',
            'usuario',
            'archivo_oc',
            'archivo_documento',
        ];
    }
}
