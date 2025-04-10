<?php

namespace App\Imports;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Models\CentroCosto;
use App\Models\FormaPago;
use App\Models\TipoPago;
use App\Models\PlazoPago;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;

class CompraImport implements ToModel, WithHeadingRow, WithEvents
{
    public $importadas = 0;
    public $omitidas = 0;
    public $errores = [];

    public function model(array $row)
    {
        Log::info('Procesando fila:', $row);

        $buscar = fn($model, $col, $valor) =>
            $model::whereRaw("LOWER($col) = ?", [Str::lower(trim($valor))])->first()?->id;

        $empresa_id       = $buscar(Empresa::class, 'nombre', $row['empresa']);
        $proveedor_id     = $buscar(Proveedor::class, 'razon_social', $row['proveedor']);
        $centro_costo_id  = $buscar(CentroCosto::class, 'nombre', $row['centro_costo']);
        $tipo_pago_id     = $buscar(TipoPago::class, 'nombre', $row['tipo_pago']);
        $plazo_pago_id    = $buscar(PlazoPago::class, 'nombre', $row['plazo_pago']);
        $forma_pago_id    = $buscar(FormaPago::class, 'nombre', $row['forma_pago']);

        // Validación
        if (!$empresa_id || !$proveedor_id || !$centro_costo_id || !$tipo_pago_id || !$plazo_pago_id || !$forma_pago_id) {
            $this->errores[] = "Fila omitida — proveedor: {$row['proveedor']}, documento: {$row['numero_documento']}";
            $this->omitidas++;
            return null;
        }

        $fecha_vencimiento = is_numeric($row['fecha_vencimiento'])
            ? Carbon::createFromDate(1899, 12, 30)->addDays($row['fecha_vencimiento'])
            : null;

        $fecha_documento = is_numeric($row['fecha_documento'])
            ? Carbon::createFromDate(1899, 12, 30)->addDays($row['fecha_documento'])
            : now();

        $this->importadas++;

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
            'fecha_vencimiento' => $fecha_vencimiento,
            'año'               => $row['ano'],
            'mes'               => $row['mes'],
            'fecha_documento'   => $fecha_documento,
            'numero_documento'  => $row['numero_documento'],
            'oc'                => $row['oc'],
            'archivo_oc'        => null,
            'archivo_documento' => null,
            'user_id'           => auth()->id(),
            'status'            => $row['status'] ?? 'Pendiente',
        ]);
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterImport::class => function () {
                session()->flash('import_result', [
                    'importadas' => $this->importadas,
                    'omitidas' => $this->omitidas,
                    'errores' => $this->errores,
                ]);
            },
        ];
    }
}
