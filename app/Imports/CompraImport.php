<?php

namespace App\Imports;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Models\CentroCosto;
use App\Models\FormaPago;
use App\Models\TipoDocumento;
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
        Log::info('📥 Fila recibida para importación:', $row);

        $buscar = function ($model, $col, $valor) {
            $valorLimpio = trim($valor);
        
            if (is_numeric($valorLimpio)) {
                $registro = $model::find((int) $valorLimpio);
                if ($registro) {
                    Log::debug("🔎 {$model} encontrado por ID: {$valorLimpio}");
                    return $registro->id;
                }
            }
        
            $valorNormalizado = $this->normalizarNombre($valorLimpio);
            return $model::all()->first(function ($item) use ($col, $valorNormalizado) {
                return $this->normalizarNombre($item->$col) === $valorNormalizado;
            })?->id;
        };
        

        // Empresa
        $empresa_id = $buscar(Empresa::class, 'Nombre', $row['empresa']);
        Log::debug("🔍 Empresa buscada: '{$row['empresa']}' → ID: {$empresa_id}");

        // Proveedor (por RUT o nombre)
        $proveedor_id = null;
        if (!empty($row['rut'])) {
            $rut_normalizado = Str::lower(str_replace('-', '', trim($row['rut'])));
            $proveedor_id = Proveedor::all()->first(function ($p) use ($rut_normalizado) {
                return Str::lower(str_replace('-', '', trim($p->rut))) === $rut_normalizado;
            })?->id;
            Log::debug("🔍 Proveedor por RUT [{$row['rut']}] → ID: {$proveedor_id}");
        }
        if (!$proveedor_id) {
            $proveedor_id = $buscar(Proveedor::class, 'razon_social', $row['proveedor']);
            Log::debug("🔍 Proveedor por nombre [{$row['proveedor']}] → ID: {$proveedor_id}");
        }

        $centro_costo_id   = $buscar(CentroCosto::class, 'nombre', $row['centro_costo']);
        $tipo_documento_id = $buscar(TipoDocumento::class, 'nombre', $row['tipo_de_documento']);
        $plazo_pago_id     = $buscar(PlazoPago::class, 'nombre', $row['plazo_pago']);
        $forma_pago_id     = $buscar(FormaPago::class, 'nombre', $row['forma_pago']);

        Log::debug("✅ Centro de Costo: {$centro_costo_id}");
        Log::debug("✅ Tipo Documento: {$tipo_documento_id}");
        Log::debug("✅ Plazo Pago: {$plazo_pago_id}");
        Log::debug("✅ Forma Pago: {$forma_pago_id}");

        // Validación final
        if (!$empresa_id || !$proveedor_id || !$centro_costo_id || !$tipo_documento_id || !$plazo_pago_id || !$forma_pago_id) {
            Log::warning("⚠️ Fila omitida por datos faltantes", [
                'empresa_id' => $empresa_id,
                'proveedor_id' => $proveedor_id,
                'centro_costo_id' => $centro_costo_id,
                'tipo_documento_id' => $tipo_documento_id,
                'plazo_pago_id' => $plazo_pago_id,
                'forma_pago_id' => $forma_pago_id,
            ]);

            $this->errores[] = "Fila omitida — proveedor: {$row['proveedor']}, documento: {$row['numero_documento']}";
            $this->omitidas++;
            return null;
        }

        // Fechas
        $fecha_vencimiento = is_numeric($row['fecha_vencimiento'])
            ? Carbon::createFromDate(1899, 12, 30)->addDays($row['fecha_vencimiento'])
            : null;

        $fecha_documento = is_numeric($row['fecha_documento'])
            ? Carbon::createFromDate(1899, 12, 30)->addDays($row['fecha_documento'])
            : now();

        $this->importadas++;
        Log::info("✅ Fila lista para guardar: documento {$row['numero_documento']}");

        return new Compra([
            'empresa_id'        => $empresa_id,
            'proveedor_id'      => $proveedor_id,
            'centro_costo_id'   => $centro_costo_id,
            'tipo_pago_id'      => $tipo_documento_id,
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

    private function normalizarNombre($valor)
    {
        return Str::of($valor)
            ->lower()
            ->replace(['.', ',', '  '], '')
            ->trim()
            ->__toString();
    }
}
