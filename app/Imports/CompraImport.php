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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;

class CompraImport implements ToModel, WithHeadingRow
{
    public $importadas = 0;
    public $omitidas = 0;
    public $errores = [];
    public $importadasDetalle = [];
    public $proveedoresFaltantes = [];

    

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

        $proveedor_id = null;
        $proveedor_rut = null;


        if (!empty($row['rut'])) {
            $rut_normalizado = Str::lower(str_replace('-', '', trim($row['rut'])));
            $proveedor = Proveedor::all()->first(function ($p) use ($rut_normalizado) {
                return Str::lower(str_replace('-', '', trim($p->rut))) === $rut_normalizado;
            });

            $proveedor_id = $proveedor?->id;
            $proveedor_rut = $proveedor?->rut;
        }

        if (!$proveedor_id) {
            $proveedor = Proveedor::all()->first(function ($p) use ($row) {
                return $this->normalizarNombre($p->razon_social) === $this->normalizarNombre($row['proveedor']);
            });

            $proveedor_id = $proveedor?->id;
            $proveedor_rut = $proveedor?->rut;
        }

        // Buscar tipo_documento_id luego de tener proveedor_id
        $tipo_documento_id = $buscar(TipoDocumento::class, 'nombre', $row['tipo_de_documento']);

        if (!$tipo_documento_id && $proveedor_id) {
            $tipo_documento_id = Proveedor::find($proveedor_id)?->tipo_pago_id;
        }

        
        $plazo_pago_id     = $buscar(PlazoPago::class, 'nombre', $row['plazo_pago']);
        $forma_pago_id     = $buscar(FormaPago::class, 'nombre', $row['forma_pago']);



        $erroresCampos = [];

        if (!$empresa_id) {
            $erroresCampos[] = 'La empresa ingresada <strong>"' . e($row['empresa']) . '"</strong> no existe.';
        }





        if (!$proveedor_id) {
            $erroresCampos[] = 'El proveedor ingresado <strong>"' . e($row['proveedor']) . '"</strong> no existe.';
        
            $nombreProveedor = $row['proveedor'] ?? '';
        
            // ✅ Solo agregamos si no existe aún
            if (!collect($this->proveedoresFaltantes)->pluck('razon_social')->contains($nombreProveedor)) {
                Log::info('Proveedor faltante registrado (único)', ['proveedor' => $nombreProveedor]);
        
                $this->proveedoresFaltantes[] = [
                    'razon_social'             => $row['proveedor'] ?? '',
                    'rut'                      => $row['rut'] ?? '',
                    'banco'                    => '',
                    'tipo_cuenta'              => '',
                    'nro_cuenta'               => '',
                    'tipo_de_documento'        => $row['tipo_de_documento'] ?? '',
                    'telefono_empresa'         => '',
                    'nombre_representantelegal'=> '',
                    'rut_representantelegal'   => '',
                    'telefono_representantelegal' => '',
                    'correo_representantelegal'=> '',
                    'contacto_nombre'          => '',
                    'contacto_telefono'        => '',
                    'contacto_correo'          => '',
                    'giro_comercial'           => '',
                    'direccion_facturacion'    => '',
                    'direccion_despacho'       => '',
                    'nombre_contacto2'         => '',
                    'telefono_contacto2'       => '',
                    'correo_contacto2'         => '',
                    'correo_banco'             => '',
                    'nombre_razon_social_banco'=> '',
                    'cargo_contacto1'          => '',
                    'cargo_contacto2'          => '',
                    'comuna_empresa'           => '',
                ];
                
            } else {
                Log::info('Proveedor faltante ya registrado anteriormente.', ['proveedor' => $nombreProveedor]);
            }
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


            $mensaje = <<<HTML
            <div style="background-color:#fff5f5; border-left:4px solid #f44336; padding:15px; margin-bottom:15px; font-family:sans-serif;">
                <p style="margin:0 0 8px;"><strong>❌ Error al importar la fila</strong> — <strong>Documento:</strong> {$docNumero}</p>
                <ul style="margin:0 0 10px 20px; padding:0;">
            HTML;

            foreach ($erroresCampos as $error) {
                $mensaje .= "<li style=\"margin-bottom:5px;\">{$error}</li>";
            }

            $mensaje .= <<<HTML
                </ul>
                <p style="color:#555; font-size:13px; margin:0;">
                    💡 <em>Revisa si hay errores de escritura o si falta registrar estos valores en el sistema.</em>
                </p>
            </div>
            HTML;




            $this->errores[] = $mensaje;
            $this->omitidas++;
            return null;
        }




        // 🔍 Validar duplicados
        $existe = Compra::where('tipo_pago_id', $tipo_documento_id)
            ->where('numero_documento', $row['numero_documento'])
            ->where('proveedor_id', $proveedor_id)
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

        $this->importadasDetalle[] = "{$row['empresa']} — {$row['proveedor']} — Doc: {$row['tipo_de_documento']} N° {$row['numero_documento']}";


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

            'archivo_oc'        => $this->esUrlValida($row['archivo_oc']) ? $row['archivo_oc'] : null,
            'archivo_documento' => $this->esUrlValida($row['archivo_documento']) ? $row['archivo_documento'] : null,



            'user_id'           => $user_id,
            'status'            => $row['status'] ?? 'Pendiente',
        ]);
    }

    private function normalizarNombre($valor)
    {
        return Str::of($valor)
            ->lower()
            ->replace(['.', ',', '  '], '')
            ->trim()
            ->__toString();
    }

    private function esUrlValida($valor)
    {
        return filter_var($valor, FILTER_VALIDATE_URL);
    }

}
