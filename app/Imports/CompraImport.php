<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\CentroCosto;
use App\Models\PlazoPago;
use App\Models\FormaPago;
use App\Models\TipoDocumento;
use Carbon\Carbon; // al inicio del archivo

class CompraImport implements ToCollection, WithHeadingRow 
{


    public $rowsData = [];
    protected $seenKeys = [];

    public $proveedoresFaltantes = [];


    public $errorMessages = [
        'fk' => [],
        'duplicados' => []
    ];



    public function collection(Collection $rows)
    {
        $ordenEsperado = [
        'empresa', 'rut', 'proveedor', 'centro_costo', 'glosa',
        'observacion', 'tipo_de_documento', 'plazo_pago', 'forma_pago',
        'pago_total', 'fecha_vencimiento', 'ano', 'mes', 'fecha_documento',
        'numero_documento', 'oc', 'status', 'usuario', 'archivo_oc', 'archivo_documento',
        ];

        $headersArchivo = array_keys($rows->first()->toArray());

        if ($headersArchivo !== $ordenEsperado) {
            // Detener y lanzar un error legible
            throw new \Exception("❌ El archivo no respeta el orden de la plantilla.");
        
        }



        $validacionesFK = [
            'empresa' => [
                'modelo' => Empresa::class,
                'campo'  => 'Nombre',
                'mensaje' => "La empresa '%s' no existe. Proveedor: %s"
            ],
            'proveedor' => [
                'modelo' => Proveedor::class,
                // El proveedor es especial porque puede buscar por RUT o por razón social,
                // esto lo vamos a manejar aparte en el bucle.
                'campo'  => 'razon_social',
                'mensaje' => "El proveedor '%s' no existe. RUT: %s"
            ],
            'centro_costo' => [
                'modelo' => CentroCosto::class,
                'campo'  => 'nombre',
                'mensaje' => "El centro de costo '%s' no existe. Proveedor: %s"
            ],
            'tipo_de_documento' => [
                'modelo' => TipoDocumento::class,
                'campo'  => 'nombre',
                'mensaje' => "El tipo de documento '%s' no existe. Proveedor: %s"
            ],
            'plazo_pago' => [
                'modelo' => PlazoPago::class,
                'campo'  => 'nombre',
                'mensaje' => "El plazo de pago '%s' no existe. Proveedor: %s"
            ],
            'forma_pago' => [
                'modelo' => FormaPago::class,
                'campo'  => 'nombre',
                'mensaje' => "La forma de pago '%s' no existe. Proveedor: %s"
            ]
        ];



        foreach ($rows as $row) {
            $fila = $row->toArray();

            // --- Validaciones de FK ---
            foreach ($validacionesFK as $columna => $config) {
                $modelo = $config['modelo'];
                $campo  = $config['campo'];

                // Caso especial: proveedor
                if ($columna === 'proveedor') {
                    $registroEncontrado = null;
                    if (!empty($row['rut'])) {
                        $registroEncontrado = Proveedor::where('rut', $row['rut'])->first();
                    }
                    if (!$registroEncontrado && !empty($row[$columna])) {
                        $registroEncontrado = $modelo::where($campo, $row[$columna])->first();
                    }
                } else {
                    $registroEncontrado = !empty($row[$columna])
                        ? $modelo::where($campo, $row[$columna])->first()
                        : null;
                }

                if (!$registroEncontrado) {
                    if ($columna === 'proveedor') {
                        $this->errorMessages['fk'][] = sprintf(
                            $config['mensaje'],
                            $row[$columna], // razón social
                            $row['rut']     // rut real
                        );

                        if (!empty($row['rut']) && !empty($row[$columna])) {
                            $this->proveedoresFaltantes[] = [
                                'rut' => $row['rut'],
                                'razon_social' => $row[$columna],
                            ];
                        }
                    } else {
                        $this->errorMessages['fk'][] = sprintf(
                            $config['mensaje'],
                            $row[$columna],
                            $row['proveedor']
                        );
                    }
                }



                $pos = array_search($columna, array_keys($fila));
                $fila = array_slice($fila, 0, $pos, true)
                    + [$columna . '_status' => $registroEncontrado ? '✅ OK' : '❌ No encontrado']
                    + array_slice($fila, $pos, null, true);
            }

            $duplicado = false;
            if (
                ($fila['proveedor_status'] ?? '') === '✅ OK' &&
                ($fila['tipo_de_documento_status'] ?? '') === '✅ OK' &&
                $fila['numero_documento'] !== null && $fila['numero_documento'] !== '' // 👈 permite 0
            ) {
                $proveedorId = Proveedor::where('rut', $row['rut'])
                    ->orWhere('razon_social', $row['proveedor'])
                    ->value('id');

                $tipoDocId = TipoDocumento::where('nombre', $row['tipo_de_documento'])->value('id');

                if ($proveedorId && $tipoDocId) {
                    $duplicado = \App\Models\Compra::where('proveedor_id', $proveedorId)
                        ->where('numero_documento', $row['numero_documento'])
                        ->where('tipo_pago_id', $tipoDocId)
                        ->exists();
                }

                // ✅ Verificar también duplicados dentro del mismo archivo
                $clave = $proveedorId 
                    . '-' . $tipoDocId 
                    . '-' . $row['numero_documento'] 
                    . '-' . (float) $fila['pago_total'] 
                    . '-' . $row['ano'] 
                    . '-' . $row['mes']
                    . '-' . trim($row['glosa']);;

                if (in_array($clave, $this->seenKeys)) {
                    $duplicado = true;
                } else {
                    $this->seenKeys[] = $clave;
                }



            }



            $posDup = array_search('numero_documento', array_keys($fila));
            $fila = array_slice($fila, 0, $posDup + 1, true)
                + ['duplicado_status' => $duplicado ? '❌ Duplicado encontrado' : '✅ OK']
                + array_slice($fila, $posDup + 1, null, true);

            if ($duplicado) {
                $this->errorMessages['duplicados'][] = "Duplicado detectado: Proveedor '{$row['proveedor']}', Número de documento '{$row['numero_documento']}', Tipo de documento '{$row['tipo_de_documento']}'";
            }

            // --- Limpieza de pago_total ---
            if (!empty($fila['pago_total'])) {
                $pagoTotal = preg_replace('/[^\d.,]/', '', $fila['pago_total']);
                $pagoTotal = str_replace(',', '', $pagoTotal);
                $fila['pago_total'] = (float) $pagoTotal;
            } else {
                $fila['pago_total'] = null;
            }

            // --- Conversión de fecha_vencimiento ---
            if (!empty($fila['fecha_vencimiento'])) {
                if (is_numeric($fila['fecha_vencimiento'])) {
                    $fila['fecha_vencimiento'] = Carbon::createFromDate(1899, 12, 30)
                        ->addDays($fila['fecha_vencimiento'])
                        ->format('Y-m-d');
                } else {
                    try {
                        $fila['fecha_vencimiento'] = Carbon::parse($fila['fecha_vencimiento'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $fila['fecha_vencimiento'] = null;
                    }
                }
            }

            // --- Conversión de fecha_documento ---
            if (!empty($fila['fecha_documento'])) {
                if (is_numeric($fila['fecha_documento'])) {
                    $fila['fecha_documento'] = Carbon::createFromDate(1899, 12, 30)
                        ->addDays($fila['fecha_documento'])
                        ->format('Y-m-d');
                } else {
                    try {
                        $fila['fecha_documento'] = Carbon::parse($fila['fecha_documento'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $fila['fecha_documento'] = null;
                    }
                }
            } else {
                // 📌 Regla: si es "Contado" y hay fecha_vencimiento definida, usarla como fecha_documento
                if (
                    isset($fila['plazo_pago']) &&
                    strtolower(trim($fila['plazo_pago'])) === 'contado' &&
                    !empty($fila['fecha_vencimiento'])
                ) {
                    // En este punto fecha_vencimiento ya fue normalizada arriba (Y-m-d)
                    $fila['fecha_documento'] = $fila['fecha_vencimiento'];
                }
            }

            // --- Guardar la fila procesada ---
            $this->rowsData[] = $fila;
        }





        
    }

}