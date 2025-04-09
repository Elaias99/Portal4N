<?php

namespace App\Imports;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Models\CentroCosto;
use App\Models\FormaPago;
use App\Models\TipoPago;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CompraImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        Log::info('Procesando fila:', $row);
        // Función de ayuda para buscar por nombre flexible
        $buscar = fn($model, $col, $valor) =>
            $model::whereRaw("LOWER($col) = ?", [Str::lower(trim($valor))])->first()?->id;

        $empresa_id       = $buscar(\App\Models\Empresa::class, 'nombre', $row['empresa']);
        $proveedor_id     = $buscar(\App\Models\Proveedor::class, 'razon_social', $row['proveedor']);
        $centro_costo_id  = $buscar(\App\Models\CentroCosto::class, 'nombre', $row['centro_costo']);
        $tipo_pago_id     = $buscar(\App\Models\TipoPago::class, 'nombre', $row['tipo_pago']);
        $plazo_pago_id    = $buscar(\App\Models\PlazoPago::class, 'nombre', $row['plazo_pago']);
        $forma_pago_id    = $buscar(\App\Models\FormaPago::class, 'nombre', $row['forma_pago']);

        if (!$empresa_id || !$proveedor_id || !$centro_costo_id) {
            Log::info('Fila omitida por datos no encontrados', $row);
            return null;
        }

        return new Compra([
            'empresa_id'        => $empresa_id,
            'proveedor_id'      => $proveedor_id,
            'centro_costo_id'   => $centro_costo_id,
            'tipo_pago_id'      => $tipo_pago_id,
            'plazo_pago_id'     => $plazo_pago_id,
            'forma_pago_id'     => $forma_pago_id,
            'glosa'             => $row['glosa'],
            'observacion'       => $row['observacion'],
            'pago_total'        => $row['pago_total'],
            'fecha_vencimiento' => $this->excelDateToCarbon($row['fecha_vencimiento']),
            'año'               => $row['ano'],
            'mes'               => $row['mes'],
            'fecha_documento' => $row['fecha_documento'] ? $this->excelDateToCarbon($row['fecha_documento']) : now(),
            'numero_documento'  => $row['numero_documento'],
            'oc'                => $row['oc'],
            'archivo_oc'        => null,
            'archivo_documento' => null,
            'user_id'           => auth()->id(),
            'status'            => $row['status'] ?? 'Pendiente',
        ]);
    }

    private function excelDateToCarbon($excelDate)
    {
        try {
            // Excel starts counting from Jan 1, 1900
            return \Carbon\Carbon::createFromDate(1899, 12, 30)->addDays($excelDate);
        } catch (\Exception $e) {
            Log::warning("Fecha inválida: " . $excelDate);
            return null;
        }
    }

    
}

