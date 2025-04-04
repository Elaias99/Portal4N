<?php

namespace App\Imports;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Models\CentroCosto;
use App\Models\FormaPago;
use App\Models\TipoPago;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\PlazoPago;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CompraImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Log::info('📥 Procesando fila:', $row);

        $empresa_id = Empresa::where('Nombre', $row['empresa'])->first()?->id;
        $proveedor_id = Proveedor::where('razon_social', $row['proveedor'])->first()?->id;
        $centro_costo_id = CentroCosto::where('nombre', $row['centro_costo'])->first()?->id;
        $tipo_pago_id = TipoPago::where('nombre', $row['tipo_pago_id'])->first()?->id;
        $plazo_pago_id = \App\Models\PlazoPago::where('nombre', $row['plazo_pago'])->first()?->id;
        $forma_pago_id = FormaPago::where('nombre', $row['forma_pago'])->first()?->id;

        // Log::info('🔗 Relaciones resueltas:', compact(
        //     'empresa_id', 'proveedor_id', 'centro_costo_id', 'tipo_pago_id', 'plazo_pago_id', 'forma_pago_id'
        // ));

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
            'fecha_vencimiento' => $row['fecha_vencimiento'],
            'año'               => $row['ano'],
            'mes'               => $row['mes'],
            'fecha_documento'   => $row['fecha_documento'],
            'numero_documento'  => $row['numero_documento'],
            'oc'                => $row['oc'],
            'archivo_oc'        => null,
            'archivo_documento' => null,
            'user_id'           => auth()->id(),
            'status'            => $row['status'] ?? 'Pendiente',
        ]);
    }
}

