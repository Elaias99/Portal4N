<?php

namespace App\Services\Courier;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/*
 * Carga Llaves_Courier.xlsx (los maestros de la llave de pago de
 * Operaciones) en las tablas courier_llave_*, la hoja Peumo en
 * courier_tarifas_peumo, la hoja Tarifas (los centros de costo) en
 * courier_tarifas y, de la hoja Proveedores, los RUT que falten en
 * courier_proveedores.
 *
 * Cada carga reemplaza los maestros completos: el archivo es la versión
 * entera, no un agregado. Las tarifas se actualizan por número y no se
 * borra ninguna. Las columnas se buscan por su nombre en la fila 1, así
 * que el orden no importa.
 */
class CourierLlavesService
{
    /* Kilos con valor propio en una tarifa; desde el 21 se suma el kilo adicional. */
    private const KILOS = 20;

    /* hoja => [tabla, [columna del Excel => campo]] */
    private const HOJAS = [
        'Agentes' => ['agentes', [
            'Agente' => 'agente',
            'RUT Proveedor' => 'rut_proveedor',
            'Razón social' => 'razon_social',
        ]],
        'Clientes' => ['clientes', [
            'Comerciante' => 'comerciante',
            'RUT Cliente' => 'rut_cliente',
            'Razón social' => 'razon_social',
        ]],
        'Servicios' => ['servicios', [
            'Servicio' => 'servicio',
            'Código' => 'codigo',
        ]],
        'Llaves' => ['llaves', [
            'RUT Proveedor' => 'rut_proveedor',
            'Agente' => 'agente',
            'RUT Cliente' => 'rut_cliente',
            'Comerciante' => 'comerciante',
            'Código Servicio' => 'codigo_servicio',
            'Servicio' => 'servicio',
            'Pagar' => 'pagar',
            'Tabla' => 'tabla',
        ]],
        'Repartidores 4N' => ['repartidores', [
            'RUT Proveedor' => 'rut_proveedor',
            'Agente' => 'agente',
            'Repartidor' => 'repartidor',
            'Nuevo RUT Proveedor' => 'nuevo_rut_proveedor',
        ]],
        'Peumo' => ['peumo', [
            'Localidad' => 'localidad',
            'Primer bulto' => 'primer_bulto',
            'Resto de los bultos' => 'resto',
        ]],
        'Proveedores' => ['proveedores', [
            'RUT Proveedor' => 'rut_proveedor',
            'Razón social' => 'razon_social',
            'Tipo documento' => 'tipo_documento',
            'Operador' => 'operador',
        ]],
    ];

    /**
     * Lee y valida el archivo sin tocar la base.
     *
     * @return array<string, list<array<string, mixed>>>  agentes, clientes, servicios, llaves, repartidores
     */
    public function leer(string $ruta): array
    {
        $hojas = $this->hojas();

        $lector = IOFactory::createReaderForFile($ruta);
        $lector->setReadDataOnly(true);
        $lector->setLoadSheetsOnly(array_keys($hojas));
        $libro = $lector->load($ruta);

        $filas = [];

        foreach ($hojas as $nombre => [$grupo, $columnas]) {
            $hoja = $libro->getSheetByName($nombre);

            if ($hoja === null) {
                throw new RuntimeException("Falta la hoja «{$nombre}».");
            }

            $filas[$grupo] = $this->filasDeHoja($hoja, $nombre, $columnas);
        }

        $this->validar($filas);

        return $filas;
    }

    /** @return array<string, int> filas guardadas por grupo */
    public function importar(string $ruta): array
    {
        $filas = $this->leer($ruta);
        $ahora = now()->format('Y-m-d H:i:s');

        $registros = [
            'courier_llave_agentes' => array_map(fn ($f) => [
                'agente' => $f['agente'],
                'agente_clave' => CourierLlaves::clave($f['agente']),
                'rut_proveedor' => CourierLlaves::rut($f['rut_proveedor']),
                'razon_social' => $f['razon_social'],
            ], $filas['agentes']),
            'courier_llave_clientes' => array_map(fn ($f) => [
                'comerciante' => $f['comerciante'],
                'comerciante_clave' => CourierLlaves::claveTexto($f['comerciante']),
                'rut_cliente' => CourierLlaves::rut($f['rut_cliente']),
                'razon_social' => $f['razon_social'],
            ], $filas['clientes']),
            'courier_llave_servicios' => array_map(fn ($f) => [
                'servicio' => $f['servicio'],
                'servicio_clave' => CourierLlaves::claveTexto($f['servicio']),
                'codigo' => (int) $f['codigo'],
            ], $filas['servicios']),
            'courier_llaves' => array_map(fn ($f) => [
                'rut_proveedor' => CourierLlaves::rut($f['rut_proveedor']),
                'agente' => $f['agente'],
                'agente_clave' => $f['agente'] === null ? null : CourierLlaves::clave($f['agente']),
                'rut_cliente' => CourierLlaves::rut($f['rut_cliente']),
                'comerciante' => $f['comerciante'],
                'codigo_servicio' => (int) $f['codigo_servicio'],
                'servicio' => $f['servicio'],
                'pagar' => strtoupper($f['pagar']),
                'tabla' => $f['tabla'] === null ? null : (int) $f['tabla'],
            ], $filas['llaves']),
            'courier_llave_repartidores' => array_map(fn ($f) => [
                'rut_proveedor' => CourierLlaves::rut($f['rut_proveedor']),
                'agente' => $f['agente'],
                'repartidor' => $f['repartidor'],
                'clave' => CourierLlaves::claveRepartidor($f['rut_proveedor'], $f['agente'], $f['repartidor']),
                'nuevo_rut_proveedor' => CourierLlaves::rut($f['nuevo_rut_proveedor']),
            ], $filas['repartidores']),
            'courier_tarifas_peumo' => array_map(fn ($f) => [
                'localidad' => $f['localidad'],
                'localidad_clave' => CourierPeumo::claveLocalidad($f['localidad']),
                'primer_bulto' => (int) $f['primer_bulto'],
                'resto' => (int) $f['resto'],
            ], $filas['peumo']),
        ];

        $agregados = 0;

        DB::transaction(function () use ($registros, $filas, $ahora, &$agregados) {
            foreach ($registros as $tabla => $filasTabla) {
                DB::table($tabla)->delete();

                foreach (array_chunk($filasTabla, 500) as $lote) {
                    DB::table($tabla)->insert(array_map(
                        fn ($f) => $f + ['created_at' => $ahora, 'updated_at' => $ahora],
                        $lote
                    ));
                }
            }

            $this->guardarTarifas($filas['tarifas'], $ahora);
            $agregados = $this->agregarProveedores($filas['proveedores'], $ahora);
        });

        return array_map('count', $filas) + ['proveedores_agregados' => $agregados];
    }

    /*
     * Proveedores que cobran y no están en courier_proveedores (catálogo
     * que viene de DatosProveedores). Sólo se agregan los RUT que faltan;
     * los que ya están no se tocan, porque ahí viven sus datos bancarios.
     * La llave "rut:…" los distingue de las filas operador + usuario.
     */
    private function agregarProveedores(array $proveedores, string $ahora): int
    {
        $existentes = DB::table('courier_proveedores')
            ->whereNotNull('rut')
            ->pluck('rut')
            ->map(fn ($rut) => CourierProveedoresPorRut::normalizar($rut))
            ->flip();

        $agregados = 0;

        foreach ($proveedores as $fila) {
            $clave = CourierProveedoresPorRut::normalizar($fila['rut_proveedor']);

            if ($clave === '' || $existentes->has($clave)) {
                continue;
            }

            DB::table('courier_proveedores')->insert([
                'operador' => Str::limit($fila['operador'] ?? $fila['razon_social'], 100, ''),
                'usuario' => '',
                'llave' => 'rut:' . $clave,
                'razon_social' => Str::limit($fila['razon_social'], 150, ''),
                'rut' => CourierLlaves::rut($fila['rut_proveedor']),
                'tipo_documento' => $fila['tipo_documento'],
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $existentes->put($clave, true);
            $agregados++;
        }

        return $agregados;
    }

    /* Cada tarifa del archivo reemplaza la del mismo número; las que no vienen se conservan. */
    private function guardarTarifas(array $tarifas, string $ahora): void
    {
        foreach ($tarifas as $fila) {
            $numero = (int) $fila['tabla'];
            $id = DB::table('courier_tarifas')->where('numero', $numero)->value('id');

            if ($id === null) {
                $id = DB::table('courier_tarifas')->insertGetId([
                    'numero' => $numero,
                    'nombre' => $fila['nombre'] ?? "Tabla {$numero}",
                    'kilo_adicional' => (int) $fila['kilo_adicional'],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            } else {
                DB::table('courier_tarifas')->where('id', $id)->update([
                    'kilo_adicional' => (int) $fila['kilo_adicional'],
                    'updated_at' => $ahora,
                ]);
            }

            DB::table('courier_tarifa_tramos')->where('courier_tarifa_id', $id)->delete();
            DB::table('courier_tarifa_tramos')->insert(array_map(fn (int $kilo) => [
                'courier_tarifa_id' => $id,
                'peso' => $kilo,
                'valor' => (int) $fila["kg{$kilo}"],
            ], range(1, self::KILOS)));
        }
    }

    /** @return array<string, array{0: string, 1: array<string, string>}> */
    private function hojas(): array
    {
        $kilos = [];
        foreach (range(1, self::KILOS) as $kilo) {
            $kilos["{$kilo} kg"] = "kg{$kilo}";
        }

        return self::HOJAS + [
            'Tarifas' => ['tarifas', ['Tabla' => 'tabla', 'Nombre' => 'nombre', 'Kilo adicional' => 'kilo_adicional'] + $kilos],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function filasDeHoja(Worksheet $hoja, string $nombre, array $columnas): array
    {
        $cabecera = [];
        foreach ($hoja->getRowIterator(1, 1)->current()->getCellIterator() as $celda) {
            $cabecera[$this->claveCabecera((string) $celda->getValue())] = $celda->getColumn();
        }

        $posiciones = [];
        foreach ($columnas as $titulo => $campo) {
            $posicion = $cabecera[$this->claveCabecera($titulo)] ?? null;

            if ($posicion === null) {
                throw new RuntimeException("A la hoja «{$nombre}» le falta la columna «{$titulo}».");
            }

            $posiciones[$campo] = $posicion;
        }

        $filas = [];
        $ultima = $hoja->getHighestDataRow();

        for ($n = 2; $n <= $ultima; $n++) {
            $fila = [];
            foreach ($posiciones as $campo => $columna) {
                $valor = trim((string) $hoja->getCell($columna . $n)->getValue());
                $fila[$campo] = $valor === '' ? null : $valor;
            }

            if (array_filter($fila, fn ($v) => $v !== null) === []) {
                continue;
            }

            $fila['_fila'] = $n;
            $filas[] = $fila;
        }

        return $filas;
    }

    /* Lo que no se puede cargar se informa entero, con hoja y fila. */
    private function validar(array $filas): void
    {
        $errores = [];

        $obligatorios = [
            'agentes' => ['Agentes', ['agente', 'rut_proveedor']],
            'clientes' => ['Clientes', ['comerciante', 'rut_cliente']],
            'servicios' => ['Servicios', ['servicio', 'codigo']],
            'llaves' => ['Llaves', ['rut_proveedor', 'rut_cliente', 'codigo_servicio', 'pagar']],
            'repartidores' => ['Repartidores 4N', ['rut_proveedor', 'agente', 'repartidor', 'nuevo_rut_proveedor']],
        ];

        foreach ($obligatorios as $grupo => [$hoja, $campos]) {
            foreach ($filas[$grupo] as $fila) {
                foreach ($campos as $campo) {
                    if ($fila[$campo] === null) {
                        $errores[] = "{$hoja}, fila {$fila['_fila']}: falta {$campo}.";
                    }
                }
            }
        }

        foreach ($filas['llaves'] as $fila) {
            if ($fila['pagar'] !== null && ! in_array(strtoupper($fila['pagar']), ['SI', 'NO', 'REVISAR'], true)) {
                $errores[] = "Llaves, fila {$fila['_fila']}: Pagar debe ser SI, NO o REVISAR.";
            }
        }

        foreach ($filas['proveedores'] as $fila) {
            foreach (['rut_proveedor' => 'RUT Proveedor', 'razon_social' => 'Razón social', 'tipo_documento' => 'Tipo documento'] as $campo => $titulo) {
                if ($fila[$campo] === null) {
                    $errores[] = "Proveedores, fila {$fila['_fila']}: falta {$titulo}.";
                }
            }
        }

        foreach ($filas['peumo'] as $fila) {
            if ($fila['localidad'] === null) {
                $errores[] = "Peumo, fila {$fila['_fila']}: falta la localidad.";
            }
            foreach (['primer_bulto' => 'Primer bulto', 'resto' => 'Resto de los bultos'] as $campo => $titulo) {
                if ($fila[$campo] === null || ! ctype_digit((string) $fila[$campo])) {
                    $errores[] = "Peumo, fila {$fila['_fila']}: {$titulo} debe ser un número entero.";
                }
            }
        }

        $tablas = [];
        foreach ($filas['tarifas'] as $fila) {
            $campos = array_merge(['tabla', 'kilo_adicional'], array_map(fn ($kilo) => "kg{$kilo}", range(1, self::KILOS)));
            foreach ($campos as $campo) {
                if ($fila[$campo] === null || ! ctype_digit((string) $fila[$campo])) {
                    $errores[] = "Tarifas, fila {$fila['_fila']}: {$campo} debe ser un número entero.";
                }
            }
            if (isset($tablas[$fila['tabla']])) {
                $errores[] = "Tarifas, fila {$fila['_fila']}: repite la tabla {$fila['tabla']}.";
            }
            $tablas[$fila['tabla']] = true;
        }

        /* Una clave repetida haría que la segunda fila pise a la primera sin aviso. */
        $repetidas = [
            'Agentes' => [$filas['agentes'], fn ($f) => CourierLlaves::clave($f['agente'])],
            'Clientes' => [$filas['clientes'], fn ($f) => CourierLlaves::claveTexto($f['comerciante'])],
            'Servicios' => [$filas['servicios'], fn ($f) => CourierLlaves::claveTexto($f['servicio'])],
            'Repartidores 4N' => [$filas['repartidores'], fn ($f) => CourierLlaves::claveRepartidor($f['rut_proveedor'], $f['agente'], $f['repartidor'])],
        ];

        foreach ($repetidas as $hoja => [$filasHoja, $clave]) {
            $vistas = [];
            foreach ($filasHoja as $fila) {
                $k = $clave($fila);
                if (isset($vistas[$k])) {
                    $errores[] = "{$hoja}, fila {$fila['_fila']}: repite la fila {$vistas[$k]}.";
                }
                $vistas[$k] ??= $fila['_fila'];
            }
        }

        if ($errores !== []) {
            throw new RuntimeException("El archivo tiene errores:\n" . implode("\n", array_slice($errores, 0, 30))
                . (count($errores) > 30 ? "\n… y " . (count($errores) - 30) . ' más.' : ''));
        }
    }

    private function claveCabecera(string $texto): string
    {
        return Str::of($texto)->squish()->lower()->ascii()->toString();
    }
}
