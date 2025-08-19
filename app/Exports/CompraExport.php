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
    protected array $opciones;
    protected array $camposPermitidos = [
        'empresa', 'rut', 'proveedor', 'centro_costo', 'glosa', 'observacion',
        'tipo_de_documento', 'plazo_pago', 'forma_pago', 'pago_total',
        'fecha_vencimiento', 'año', 'mes', 'fecha_documento',
        'numero_documento', 'oc', 'status', 'usuario', 'archivo_oc', 'archivo_documento',
    ];

    public function __construct(array $opciones = [])
    {
        // Validar campos permitidos
        $this->opciones = array_values(array_intersect($opciones, $this->camposPermitidos));
    }

    public function query()
    {
        $relaciones = [];

        if (in_array('empresa', $this->opciones))      $relaciones[] = 'empresa';
        if (in_array('rut', $this->opciones) || in_array('proveedor', $this->opciones)) $relaciones[] = 'proveedor';
        if (in_array('centro_costo', $this->opciones)) $relaciones[] = 'centroCosto';
        if (in_array('tipo_de_documento', $this->opciones)) $relaciones[] = 'tipoPago';
        if (in_array('plazo_pago', $this->opciones))   $relaciones[] = 'plazoPago';
        if (in_array('forma_pago', $this->opciones))   $relaciones[] = 'formaPago';
        if (in_array('usuario', $this->opciones))      $relaciones[] = 'user';

        return Compra::with($relaciones);
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
                case 'tipo_de_documento':
                    $campos[] = $compra->tipoPago->nombre ?? 'Sin Registro';
                    break;
                case 'plazo_pago':
                    $campos[] = $compra->plazoPago->nombre ?? 'Sin Registro';
                    break;
                case 'forma_pago':
                    $campos[] = $compra->formaPago->nombre ?? 'Sin Registro';
                    break;
                case 'usuario':
                    $campos[] = $compra->user->name ?? 'Sin Registro';
                    break;


                case 'fecha_vencimiento':
                case 'fecha_documento':
                    $campos[] = $compra->{$campo}?->format('Y-m-d'); // ya es Carbon por el cast
                    break;



                default:
                    $campos[] = $compra->{$campo} ?? '—';
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

        return array_map(fn($key) => $titulos[$key] ?? ucfirst($key), $this->opciones);
    }
}
