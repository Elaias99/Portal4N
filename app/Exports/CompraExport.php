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
    protected $opciones;

    public function __construct(array $opciones = [])
    {
        $this->opciones = $opciones;
    }

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
        $campos = [];

        foreach ($this->opciones as $campo) {
            switch ($campo) {
                case 'empresa':
                    $campos[] = $compra->empresa->Nombre ?? 'Sin Registro';
                    break;
                case 'rut':
                    $campos[] = $compra->proveedor->rut ?? 'Sin Registro';
                    break;
                case 'proveedor':
                    $campos[] = $compra->proveedor->razon_social ?? 'Sin Registro';
                    break;
                case 'centro_costo':
                    $campos[] = $compra->centroCosto->nombre ?? 'Sin Registro';
                    break;
                case 'glosa':
                    $campos[] = $compra->glosa;
                    break;
                case 'observacion':
                    $campos[] = $compra->observacion;
                    break;
                case 'tipo_de_documento':
                    $campos[] = $compra->tipoPago->nombre ?? 'Sin Registro';
                    break;
                case 'plazo_pago':
                    $campos[] = $compra->plazoPago->nombre ?? 'Sin Registro';
                    break;
                case 'forma_pago':
                    $campos[] = $compra->formaPago->nombre ?? 'Sin Registro';
                    break;
                case 'pago_total':
                    $campos[] = $compra->pago_total;
                    break;
                case 'fecha_vencimiento':
                    $campos[] = optional($compra->fecha_vencimiento)->format('Y-m-d');
                    break;
                case 'año':
                    $campos[] = $compra->año;
                    break;
                case 'mes':
                    $campos[] = $compra->mes;
                    break;
                case 'fecha_documento':
                    $campos[] = optional($compra->fecha_documento)->format('Y-m-d');
                    break;
                case 'numero_documento':
                    $campos[] = $compra->numero_documento;
                    break;
                case 'oc':
                    $campos[] = $compra->oc;
                    break;
                case 'status':
                    $campos[] = $compra->status;
                    break;
                case 'usuario':
                    $campos[] = $compra->user->name ?? 'Sin Registro';
                    break;
                case 'archivo_oc':
                    $campos[] = $compra->archivo_oc;
                    break;
                case 'archivo_documento':
                    $campos[] = $compra->archivo_documento;
                    break;
            }
        }

        return $campos;
    }

    public function headings(): array
    {
        $titulos = [
            'empresa' => 'Empresa',
            'rut' => 'RUT',
            'proveedor' => 'Proveedor',
            'centro_costo' => 'Centro de Costo',
            'glosa' => 'Glosa',
            'observacion' => 'Observación',
            'tipo_de_documento' => 'Tipo de Documento',
            'plazo_pago' => 'Plazo de Pago',
            'forma_pago' => 'Forma de Pago',
            'pago_total' => 'Pago Total',
            'fecha_vencimiento' => 'Fecha de Vencimiento',
            'año' => 'Año',
            'mes' => 'Mes',
            'fecha_documento' => 'Fecha Documento',
            'numero_documento' => 'N° Documento',
            'oc' => 'Orden de Compra',
            'status' => 'Estado',
            'usuario' => 'Usuario',
            'archivo_oc' => 'Archivo OC',
            'archivo_documento' => 'Archivo Documento',
        ];

        return array_map(fn($key) => $titulos[$key] ?? $key, $this->opciones);
    }
}
