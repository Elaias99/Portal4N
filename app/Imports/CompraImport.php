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

class CompraImport implements ToModel, WithHeadingRow
{
    public $importadas = 0;
    public $omitidas = 0;
    public $errores = [];
    public $importadasDetalle = [];
    public $proveedoresFaltantes = [];

    public $erroresDuplicados = [];
    public $erroresValidacion = [];

    protected $empresas;
    protected $centros;
    protected $documentos;
    protected $plazos;
    protected $formas;
    protected $proveedores;

    public function __construct()
    {
        $this->empresas    = Empresa::all();
        $this->centros     = CentroCosto::all();
        $this->documentos  = TipoDocumento::all();
        $this->plazos      = PlazoPago::all();
        $this->formas      = FormaPago::all();
        $this->proveedores = Proveedor::all();
    }

    public function model(array $row)
    {
        $empresa_id = $this->match($this->empresas, 'Nombre', $row['empresa']);
        $centro_costo_id = $this->match($this->centros, 'nombre', $row['centro_costo']);
        $plazo_pago_id = $this->match($this->plazos, 'nombre', $row['plazo_pago']);
        $forma_pago_id = $this->match($this->formas, 'nombre', $row['forma_pago']);

        $proveedor_id = null;
        $proveedor_rut = null;

        if (!empty($row['rut'])) {
            $rut_normalizado = Str::lower(str_replace('-', '', trim($row['rut'])));
            $proveedor = $this->proveedores->first(function ($p) use ($rut_normalizado) {
                return Str::lower(str_replace('-', '', trim($p->rut))) === $rut_normalizado;
            });

            $proveedor_id = $proveedor?->id;
            $proveedor_rut = $proveedor?->rut;
        }

        if (!$proveedor_id) {
            $proveedor = $this->proveedores->first(function ($p) use ($row) {
                return $this->normalizarNombre($p->razon_social) === $this->normalizarNombre($row['proveedor']);
            });

            $proveedor_id = $proveedor?->id;
            $proveedor_rut = $proveedor?->rut;
        }

        $tipo_documento_id = $this->match($this->documentos, 'nombre', $row['tipo_de_documento']) ?? 
                             ($proveedor_id ? Proveedor::find($proveedor_id)?->tipo_pago_id : null);

        $erroresCampos = [];

        if (!$empresa_id) $erroresCampos[] = 'La empresa ingresada <strong>"' . e($row['empresa']) . '"</strong> no existe.';
        if (!$proveedor_id) {
            $erroresCampos[] = 'El proveedor ingresado <strong>"' . e($row['proveedor']) . '"</strong> no existe.';

            $nombreProveedor = $row['proveedor'] ?? '';
            if (!collect($this->proveedoresFaltantes)->pluck('razon_social')->contains($nombreProveedor)) {
                Log::info('Proveedor faltante registrado (único)', ['proveedor' => $nombreProveedor]);
                $this->proveedoresFaltantes[] = ['razon_social' => $nombreProveedor, 'rut' => $row['rut'] ?? ''] + array_fill_keys([
                    'banco', 'tipo_cuenta', 'nro_cuenta', 'tipo_de_documento',
                    'telefono_empresa', 'nombre_representantelegal', 'rut_representantelegal',
                    'telefono_representantelegal', 'correo_representantelegal', 'contacto_nombre',
                    'contacto_telefono', 'contacto_correo', 'giro_comercial', 'direccion_facturacion',
                    'direccion_despacho', 'nombre_contacto2', 'telefono_contacto2', 'correo_contacto2',
                    'correo_banco', 'nombre_razon_social_banco', 'cargo_contacto1', 'cargo_contacto2', 'comuna_empresa'
                ], '');
            }
        }
        if (!$centro_costo_id) $erroresCampos[] = 'El centro de costo <strong>"' . e($row['centro_costo']) . '"</strong> no está registrado.';
        if (!$tipo_documento_id) $erroresCampos[] = 'El tipo de documento <strong>"' . e($row['tipo_de_documento']) . '"</strong> no coincide.';
        if (!$plazo_pago_id) $erroresCampos[] = 'El plazo de pago <strong>"' . e($row['plazo_pago']) . '"</strong> no es válido.';
        if (!$forma_pago_id) $erroresCampos[] = 'La forma de pago <strong>"' . e($row['forma_pago']) . '"</strong> no está registrada.';

        if ($erroresCampos) {
            $doc = $row['numero_documento'] ?? 'Sin número';
            $mensaje = "<div style='background:#fff5f5;border-left:4px solid #f44336;padding:15px;margin-bottom:15px;font-family:sans-serif;'>
                <p><strong>❌ Error al importar la fila</strong> — <strong>Documento:</strong> {$doc}</p><ul>";
            foreach ($erroresCampos as $e) $mensaje .= "<li>{$e}</li>";
            $mensaje .= "</ul><p style='color:#555;font-size:13px;'>💡 <em>Revisa si hay errores de escritura o si falta registrar estos valores en el sistema.</em></p></div>";
            $this->errores[] = $mensaje;
            $this->erroresValidacion[] = $mensaje;
            $this->omitidas++;
            return null;
        }

        $numeroDocumento = trim((string)($row['numero_documento'] ?? ''));
        if ($numeroDocumento === '' || $numeroDocumento === '0') {
            $prov = $row['proveedor'] ?? 'Desconocido';
            $this->errores[] = "<div style='background:#fff5f5;border-left:4px solid #f44336;padding:15px;margin-bottom:15px;'>
                <p><strong>❌ Número de documento inválido</strong></p>
                <ul><li>Proveedor: <strong>{$prov}</strong></li><li>Documento: <strong>{$numeroDocumento}</strong></li>
                <li>💡 El número de documento no puede estar vacío ni ser igual a \"0\".</li></ul></div>";
            $this->omitidas++;
            return null;
        }

        $existe = Compra::where('tipo_pago_id', $tipo_documento_id)
            ->where('numero_documento', $numeroDocumento)
            ->where('proveedor_id', $proveedor_id)
            ->exists();

        if ($existe) {
            Log::info('⚠️ Compra omitida por duplicado', ['proveedor_id' => $proveedor_id, 'numero_documento' => $numeroDocumento]);
            $empresaNombre = Empresa::find($empresa_id)?->Nombre ?? $row['empresa'];
            $proveedorNombre = Proveedor::find($proveedor_id)?->razon_social ?? $row['proveedor'];
            $tipoNombre = TipoDocumento::find($tipo_documento_id)?->nombre ?? $row['tipo_de_documento'];

            $mensaje = "⚠️ Duplicado — Empresa: {$empresaNombre}, Proveedor: {$proveedorNombre}, Tipo Doc: {$tipoNombre}, N° Doc: {$numeroDocumento}";
            $this->erroresDuplicados[] = $mensaje;
            $this->errores[] = $mensaje;
            $this->omitidas++;
            return null;

            
            $this->erroresDuplicados[] = end($this->errores);
            $this->omitidas++;
            return null;
        }

        $fecha_documento = is_numeric($row['fecha_documento'])
            ? Carbon::createFromDate(1899, 12, 30)->addDays($row['fecha_documento'])
            : now();

        // Calcular fecha de vencimiento si el plazo es válido
        $plazo_nombre = PlazoPago::find($plazo_pago_id)?->nombre;

        switch ($plazo_nombre) {
            case 'Contado':
                $fecha_vencimiento = $fecha_documento->copy();
                break;
            case 'Quincena':
                $fecha_vencimiento = $this->proxViernes($fecha_documento->copy()->addDays(15));
                break;
            case '30 Días':
                $fecha_vencimiento = $this->proxViernes($fecha_documento->copy()->addDays(30));
                break;
            case '45 Días':
                $fecha_vencimiento = $this->proxViernes($fecha_documento->copy()->addDays(45));
                break;
            case '60 Días':
                $fecha_vencimiento = $this->proxViernes($fecha_documento->copy()->addDays(60));
                break;
            case '1 Semana':
                $fecha_vencimiento = $this->proxViernes($fecha_documento->copy()->addDays(7));
                break;
            default:
                // Si es un plazo no reconocido (ej: "2 Cuotas"), usar el valor del Excel si viene
                $fecha_vencimiento = is_numeric($row['fecha_vencimiento'])
                    ? Carbon::createFromDate(1899, 12, 30)->addDays($row['fecha_vencimiento'])
                    : null;
                break;
        }


        $user_id = User::where('name', trim($row['usuario']))->first()?->id ?? auth()->id();

        $this->importadas++;
        $this->importadasDetalle[] = "{$row['empresa']} — {$row['proveedor']} — Doc: {$row['tipo_de_documento']} N° {$numeroDocumento}";

        return new Compra([
            'empresa_id' => $empresa_id,
            'proveedor_id' => $proveedor_id,
            'centro_costo_id' => $centro_costo_id,
            'tipo_pago_id' => $tipo_documento_id,
            'plazo_pago_id' => $plazo_pago_id,
            'forma_pago_id' => $forma_pago_id,
            'glosa' => $row['glosa'],
            'observacion' => $row['observacion'],
            'pago_total' => $this->normalizarMonto($row['pago_total'], $numeroDocumento),

            'fecha_vencimiento' => $fecha_vencimiento,
            'año' => $row['ano'],
            'mes' => $row['mes'],
            'fecha_documento' => $fecha_documento,
            'numero_documento' => $numeroDocumento,
            'oc' => $row['oc'],
            'archivo_oc' => $this->esUrlValida($row['archivo_oc']) ? $row['archivo_oc'] : null,
            'archivo_documento' => $this->esUrlValida($row['archivo_documento']) ? $row['archivo_documento'] : null,
            'user_id' => $user_id,
            'status' => $row['status'] ?? 'Pendiente',
        ]);
    }

    private function match($collection, $col, $valor)
    {
        if (!$valor) return null;

        $valNorm = strtolower(trim($valor));

        return $collection->first(function ($item) use ($col, $valNorm) {
            return strtolower(trim($item->$col)) === $valNorm;
        })?->id;
    }


    private function normalizarNombre($valor)
    {
        return Str::of($valor)->lower()->replace(['.', ',', '  '], '')->trim()->__toString();
    }

    private function esUrlValida($valor)
    {
        return filter_var($valor, FILTER_VALIDATE_URL);
    }

    private function normalizarMonto($valor, $documentoReferencia = null)
    {
        if (is_null($valor)) return null;

        // Eliminar comas, signos $ y espacios
        $limpio = str_replace([',', '$', ' '], '', (string)$valor);

        // Validar si es numérico
        if (!is_numeric($limpio)) {
            $this->errores[] = "<div style='background:#fff5f5;border-left:4px solid #f44336;padding:15px;margin-bottom:15px;'>
                <p><strong>❌ Monto inválido en 'pago_total'</strong></p>
                <ul>
                    <li>Valor original: <strong>{$valor}</strong></li>
                    <li>Documento: <strong>{$documentoReferencia}</strong></li>
                    <li>💡 Asegúrate de ingresar solo números. Puedes usar comas para miles y punto como decimal (ej: 1,000.50)</li>
                </ul></div>";
            $this->erroresValidacion[] = end($this->errores);
            $this->omitidas++;
            return null;
        }

        return round((float) $limpio, 2);
    }


    private function proxViernes(Carbon $fecha)
    {
        return $fecha->isFriday()
            ? $fecha
            : $fecha->next(Carbon::FRIDAY);
    }





}
