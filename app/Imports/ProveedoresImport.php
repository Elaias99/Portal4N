<?php

namespace App\Imports;

use App\Models\Banco;
use App\Models\Proveedor;
use App\Models\TipoCuenta;
use App\Models\TipoDocumento;
use App\Models\Comuna;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class ProveedoresImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    public $importadas = 0;
    public $omitidas = 0;
    public $errores = [];
    public $exitosos = [];
    public $conDatosIncompletos = [];

    protected $bancos;
    protected $tipoCuentas;
    protected $tipoDocumentos;
    protected $comunas;

    public function collection(Collection $rows)
    {
        $this->precargarReferencias();
        $insertLote = [];

        foreach ($rows as $row) {
            $rut = $this->normalizarRut($row['rut'] ?? '');
            $razon_social = $this->normalizarRazonSocial($row['razon_social'] ?? '');

            if (!$razon_social || !$rut) {
                $this->errores[] = "Fila omitida — falta RUT o razón social. Detectado: '{$row['razon_social']}' (RUT: {$row['rut']})";
                $this->omitidas++;
                continue;
            }

            if (Proveedor::where('rut', $rut)->orWhereRaw('UPPER(TRIM(razon_social)) = ?', [$razon_social])->exists()) {
                $this->errores[] = "Fila omitida — ya existe el proveedor '{$razon_social}' (RUT: {$rut})";
                $this->omitidas++;
                continue;
            }

            $incompletos = [];

            // BANCO
            $bancoNombre = trim($row['banco'] ?? '');
            if (strtolower($bancoNombre) === 'sin registro') {
                $banco_id = $this->buscarId($bancoNombre, $this->bancos);
                $incompletos[] = 'Banco';
            } elseif ($bancoNombre === '' || !($banco_id = $this->buscarId($bancoNombre, $this->bancos))) {
                $this->errores[] = "Fila omitida — banco '{$row['banco']}' no reconocido para '{$razon_social}' (RUT: {$rut})";
                $this->omitidas++;
                continue;
            }

            // TIPO CUENTA
            $tipoCuentaNombre = trim($row['tipo_cuenta'] ?? '');
            if (strtolower($tipoCuentaNombre) === 'sin registro') {
                $tipo_cuenta_id = $this->buscarId($tipoCuentaNombre, $this->tipoCuentas);
                $incompletos[] = 'Tipo de Cuenta';
            } elseif ($tipoCuentaNombre === '' || !($tipo_cuenta_id = $this->buscarId($tipoCuentaNombre, $this->tipoCuentas))) {
                $this->errores[] = "Fila omitida — tipo de cuenta '{$row['tipo_cuenta']}' no reconocido para '{$razon_social}' (RUT: {$rut})";
                $this->omitidas++;
                continue;
            }

            // TIPO DOCUMENTO
            $tipoDocNombre = trim($row['tipo_de_documento'] ?? '');
            if (strtolower($tipoDocNombre) === 'sin registro') {
                $tipo_pago_id = $this->buscarId($tipoDocNombre, $this->tipoDocumentos);
                $incompletos[] = 'Tipo de Documento';
            } elseif ($tipoDocNombre === '' || !($tipo_pago_id = $this->buscarId($tipoDocNombre, $this->tipoDocumentos))) {
                $this->errores[] = "Fila omitida — tipo de documento '{$row['tipo_de_documento']}' no reconocido para '{$razon_social}' (RUT: {$rut})";
                $this->omitidas++;
                continue;
            }

            // NRO CUENTA
            $nroCuenta = $row['nro_cuenta'] ?? '';
            $nroCuentaRaw = strtoupper(trim($nroCuenta));
            if ($nroCuentaRaw === 'SIN REGISTRO') {
                $incompletos[] = 'Número de Cuenta';
            } elseif ($nroCuentaRaw === '' || (!is_numeric($nroCuenta) && $nroCuentaRaw !== 'NO APLICA')) {
                $this->errores[] = "Fila omitida — número de cuenta inválido para '{$razon_social}' (RUT: {$rut})";
                $this->omitidas++;
                continue;
            }

            // COMUNA
            $comuna_id = null;
            $comunaNombre = strtoupper(trim($row['comuna_empresa'] ?? ''));
            if ($comunaNombre !== '') {
                $comuna_id = $this->buscarId($comunaNombre, $this->comunas);
            }

            $insertLote[] = [
                'razon_social' => $razon_social,
                'rut' => $rut,
                'banco_id' => $banco_id,
                'tipo_cuenta_id' => $tipo_cuenta_id,
                'tipo_pago_id' => $tipo_pago_id,
                'nro_cuenta' => $nroCuenta,
                'telefono_empresa' => $row['telefono_empresa'] ?? 'N/A',
                'Nombre_RepresentanteLegal' => $row['nombre_representantelegal'] ?? 'N/A',
                'Rut_RepresentanteLegal' => $row['rut_representantelegal'] ?? 'N/A',
                'Telefono_RepresentanteLegal' => $row['telefono_representantelegal'] ?? 'N/A',
                'Correo_RepresentanteLegal' => $row['correo_representantelegal'] ?? 'N/A',
                'contacto_nombre' => $row['contacto_nombre'] ?? 'N/A',
                'contacto_telefono' => $row['contacto_telefono'] ?? 'N/A',
                'contacto_correo' => $row['contacto_correo'] ?? 'N/A',
                'giro_comercial' => $row['giro_comercial'] ?? 'N/A',
                'direccion_facturacion' => $row['direccion_facturacion'] ?? 'N/A',
                'direccion_despacho' => $row['direccion_despacho'] ?? 'N/A',
                'nombre_contacto2' => $row['nombre_contacto2'] ?? 'N/A',
                'telefono_contacto2' => $row['telefono_contacto2'] ?? 'N/A',
                'correo_contacto2' => $row['correo_contacto2'] ?? 'N/A',
                'correo_banco' => $row['correo_banco'] ?? 'N/A',
                'nombre_razon_social_banco' => $row['nombre_razon_social_banco'] ?? 'N/A',
                'cargo_contacto1' => $row['cargo_contacto1'] ?? 'N/A',
                'cargo_contacto2' => $row['cargo_contacto2'] ?? 'N/A',
                'comuna_id' => $comuna_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            foreach ($incompletos as $campo) {
                $this->conDatosIncompletos[] = "{$razon_social} (RUT: {$rut}) — {$campo}";
            }

            $this->importadas++;
            $this->exitosos[] = "{$razon_social} (RUT: {$rut})";
        }

        if (!empty($insertLote)) {
            DB::table('proveedores')->insert($insertLote);
        }
    }

    public function chunkSize(): int { return 100; }
    public function batchSize(): int { return 100; }

    private function precargarReferencias()
    {
        $this->bancos = Banco::all()->keyBy(fn($b) => strtoupper(str_replace(' ', '', $b->nombre)));
        $this->tipoCuentas = TipoCuenta::all()->keyBy(fn($t) => strtoupper(str_replace(' ', '', $t->nombre)));
        $this->tipoDocumentos = TipoDocumento::all()->keyBy(fn($d) => strtoupper(str_replace(' ', '', $d->nombre)));
        $this->comunas = Comuna::all()->keyBy(fn($c) => strtoupper(str_replace(' ', '', $c->nombre)));
    }

    private function buscarId($raw, $coleccion)
    {
        $clave = strtoupper(str_replace(' ', '', $raw));
        return $coleccion[$clave]->id ?? null;
    }

    private function normalizarRut($rut)
    {
        if (preg_match('/\b(\d{7,8}-[0-9kK])\b/', $rut, $match)) {
            return strtoupper($match[1]);
        }
        return strtoupper(trim(preg_replace('/[^0-9kK-]/', '', $rut)));
    }

    private function normalizarRazonSocial($razon)
    {
        $razon = strtoupper(trim($razon));
        $razon = preg_replace('/\s+/', ' ', $razon);
        return rtrim($razon, '.');
    }
}
