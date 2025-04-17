<?php

namespace App\Imports;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Empresa;
use App\Models\CentroCosto;
use App\Models\FormaPago;
use App\Models\TipoDocumento;
use App\Models\PlazoPago;
use App\Models\User;

use Illuminate\Support\Str;
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
        $buscar = function ($model, $col, $valor) {
            $valorLimpio = trim($valor);

            if (is_numeric($valorLimpio)) {
                $registro = $model::find((int) $valorLimpio);
                if ($registro) {
                    return $registro->id;
                }
            }

            $valorNormalizado = $this->normalizarNombre($valorLimpio);
            return $model::all()->first(function ($item) use ($col, $valorNormalizado) {
                return $this->normalizarNombre($item->$col) === $valorNormalizado;
            })?->id;
        };

        $empresa_id        = $buscar(Empresa::class, 'Nombre', $row['empresa']);
        $centro_costo_id   = $buscar(CentroCosto::class, 'nombre', $row['centro_costo']);
        $tipo_documento_id = $buscar(TipoDocumento::class, 'nombre', $row['tipo_de_documento']);
        $plazo_pago_id     = $buscar(PlazoPago::class, 'nombre', $row['plazo_pago']);
        $forma_pago_id     = $buscar(FormaPago::class, 'nombre', $row['forma_pago']);

        $proveedor_id = null;
        if (!empty($row['rut'])) {
            $rut_normalizado = Str::lower(str_replace('-', '', trim($row['rut'])));
            $proveedor_id = Proveedor::all()->first(function ($p) use ($rut_normalizado) {
                return Str::lower(str_replace('-', '', trim($p->rut))) === $rut_normalizado;
            })?->id;
        }
        if (!$proveedor_id) {
            $proveedor_id = $buscar(Proveedor::class, 'razon_social', $row['proveedor']);
        }

        $erroresCampos = [];

        if (!$empresa_id) {
            $erroresCampos[] = 'La empresa ingresada <strong>"' . e($row['empresa']) . '"</strong> no existe.';
        }
        if (!$proveedor_id) {
            $erroresCampos[] = 'El proveedor ingresado <strong>"' . e($row['proveedor']) . '"</strong> no existe.';
        }
        if (!$centro_costo_id) {
            $erroresCampos[] = 'El centro de costo <strong>"' . e($row['centro_costo']) . '"</strong> no está registrado.';
        }
        if (!$tipo_documento_id) {
            $erroresCampos[] = 'El tipo de documento <strong>"' . e($row['tipo_de_documento']) . '"</strong> no coincide con los existentes.';
        }
        if (!$plazo_pago_id) {
            $erroresCampos[] = 'El plazo de pago <strong>"' . e($row['plazo_pago']) . '"</strong> no es válido.';
        }
        if (!$forma_pago_id) {
            $erroresCampos[] = 'La forma de pago <strong>"' . e($row['forma_pago']) . '"</strong> no está registrada.';
        }

        if (count($erroresCampos)) {
            $docNumero = $row['numero_documento'] ?? 'Sin número';
            $mensaje = "<strong>❌ No se pudo importar la fila con el documento \"{$docNumero}\".</strong><br>";
            $mensaje .= "Motivo:<ul>";
            foreach ($erroresCampos as $error) {
                $mensaje .= "<li>{$error}</li>";
            }
            $mensaje .= "</ul>";
            $mensaje .= '<em>💡 Sugerencia: Revisa si hay errores de escritura o si falta registrar estos valores en el sistema.</em>';

            $this->errores[] = $mensaje;
            $this->omitidas++;
            return null;
        }




        // 🔍 Validar duplicados
        $existe = Compra::where('tipo_pago_id', $tipo_documento_id)
            ->where('numero_documento', $row['numero_documento'])
            ->exists();

        if ($existe) {
            $tipoNombre      = \App\Models\TipoDocumento::find($tipo_documento_id)?->nombre ?? $row['tipo_de_documento'];
            $empresaNombre   = \App\Models\Empresa::find($empresa_id)?->Nombre ?? $row['empresa'];
            $proveedorNombre = \App\Models\Proveedor::find($proveedor_id)?->razon_social ?? $row['proveedor'];
            $docNumero       = $row['numero_documento'] ?? 'Sin número';

            $this->errores[] = "⚠️ Duplicado — Empresa: {$empresaNombre}, Proveedor: {$proveedorNombre}, Tipo Doc: {$tipoNombre}, N° Doc: {$docNumero}";
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

        $user_id = User::where('name', trim($row['usuario']))->first()?->id ?? auth()->id();

        $this->importadas++;

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
            'user_id'           => $user_id,
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
                    'errores'   => $this->errores,
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
