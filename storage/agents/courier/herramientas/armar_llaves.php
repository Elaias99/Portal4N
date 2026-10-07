<?php

/*
 * Arma Downloads/Llaves_Courier_202608.xlsx con los maestros de la llave de pago
 * de LogisticaCL (exportados antes en modo lectura a llaves/*.tsv).
 * No toca ninguna base.
 */

require 'C:/xampp/htdocs/Portal4N/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = __DIR__ . '/llaves';
$periodo = $argv[1] ?? '202608';
$salida = "C:/Users/elias/Downloads/Llaves_Courier_{$periodo}.xlsx";

/*
 * Agentes: la cobertura vigente de LogisticaCL más los agentes que pagó en
 * agosto y que ya no están en su cobertura (Calama cambió de agente en
 * septiembre). Así la llave de agosto queda como la aplicó Operaciones.
 */
file_put_contents("$dir/agentes_todos.tsv", file_get_contents("$dir/agentes.tsv") . file_get_contents("$dir/agentes_extra_{$periodo}.tsv"));

/*
 * Repartidores 4N: la tabla vigente, corregida con lo que Operaciones
 * aplicó en agosto (ago_4n.tsv) cuando después cambió la asignación.
 */
$tsv = fn (string $archivo) => array_map(
    fn ($l) => explode("\t", rtrim($l, "\r")),
    array_filter(file("$dir/$archivo", FILE_IGNORE_NEW_LINES), fn ($l) => trim($l) !== '')
);
$minus = fn ($s) => mb_strtolower(trim((string) $s));
$rutAgente = [];
foreach ($tsv('agentes_todos.tsv') as [$agente, $rut]) {
    $rutAgente[$minus($agente)] = $rut;
}
$repartidores = array_merge($tsv('repartidores.tsv'), $tsv("repartidores_extra_{$periodo}.tsv"));
$correcciones = 0;
foreach ($tsv("aplicado_4n_{$periodo}.tsv") as [$agente, $repartidor, $rutAgosto]) {
    $rut = $rutAgente[$minus($agente)] ?? null;
    if ($rut === null) {
        continue;
    }
    $nuevo = $rutAgosto === $rut ? 'N/A' : $rutAgosto;
    $encontrada = false;
    foreach ($repartidores as $i => [$rp, $ag, $rep, $nv]) {
        if ($rp === $rut && $minus($ag) === $minus($agente) && $minus($rep) === $repartidor) {
            $encontrada = true;
            $actual = $nv === 'N/A' ? $rut : $nv;
            if ($actual !== $rutAgosto) {
                $repartidores[$i][3] = $nuevo;
                $correcciones++;
            }
        }
    }
    if (! $encontrada && $rutAgosto !== $rut) {
        $repartidores[] = [$rut, $agente, $repartidor, $nuevo];
        $correcciones++;
    }
}
file_put_contents("$dir/repartidores_{$periodo}.tsv", implode("\n", array_map(fn ($f) => implode("\t", $f), $repartidores)) . "\n");
echo "Repartidores corregidos con lo aplicado en agosto: $correcciones\n";

$hojas = [
    'Agentes' => ['agentes_todos', ['Agente', 'RUT Proveedor', 'Razón social']],
    'Clientes' => ['clientes', ['Comerciante', 'RUT Cliente', 'Razón social']],
    'Servicios' => ['servicios', ['Servicio', 'Código']],
    'Llaves' => ['llaves', ['RUT Proveedor', 'Agente', 'RUT Cliente', 'Comerciante', 'Código Servicio', 'Servicio', 'Pagar', 'Tabla']],
    'Repartidores 4N' => ["repartidores_{$periodo}", ['RUT Proveedor', 'Agente', 'Repartidor', 'Nuevo RUT Proveedor']],
    'Peumo' => ['peumo_ok', ['Localidad', 'Primer bulto', 'Resto de los bultos']],
    'Proveedores' => ["proveedores_{$periodo}", ['RUT Proveedor', 'Razón social', 'Tipo documento', 'Operador']],
    'Tarifas' => ['tarifas', array_merge(['Tabla', 'Nombre', 'Kilo adicional'], array_map(fn ($kg) => "$kg kg", range(1, 20)))],
];

/* Columnas numéricas por hoja (índice desde 0); lo demás se escribe como texto. */
$numericas = ['Servicios' => [1], 'Peumo' => [1, 2], 'Llaves' => [4, 7], 'Tarifas' => array_merge([0], range(2, 22))];

$libro = new Spreadsheet();
$libro->removeSheetByIndex(0);

foreach ($hojas as $titulo => [$archivo, $cabecera]) {
    $hoja = $libro->createSheet();
    $hoja->setTitle($titulo);
    $hoja->fromArray($cabecera, null, 'A1');
    $ultimaColumna = Coordinate::stringFromColumnIndex(count($cabecera));
    $hoja->getStyle("A1:{$ultimaColumna}1")->getFont()->setBold(true);

    $fila = 2;
    foreach (file("$dir/$archivo.tsv", FILE_IGNORE_NEW_LINES) as $linea) {
        $linea = rtrim($linea, "\r");
        if ($linea === '') {
            continue;
        }
        foreach (explode("\t", $linea) as $i => $valor) {
            $celda = Coordinate::stringFromColumnIndex($i + 1) . $fila;
            if (in_array($i, $numericas[$titulo] ?? [], true) && $valor !== '') {
                $hoja->setCellValue($celda, (int) $valor);
            } else {
                $hoja->setCellValueExplicit($celda, $valor, DataType::TYPE_STRING);
            }
        }
        $fila++;
    }

    for ($c = 1; $c <= count($cabecera); $c++) {
        $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
    }
    $hoja->freezePane('A2');
    echo str_pad($titulo, 18) . ($fila - 2) . " filas\n";
}

$libro->setActiveSheetIndex(0);
(new Xlsx($libro))->save($salida);
echo "Guardado: $salida\n";
