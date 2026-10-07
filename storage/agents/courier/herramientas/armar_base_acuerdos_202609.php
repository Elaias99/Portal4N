<?php

/*
 * Arma Base_Acuerdos_202609.xlsx con el formato de la plantilla de
 * LogisticaCL (hojas Calendario y Base Acuerdos), a partir de lo que
 * LogisticaCL guardó para septiembre. Sólo lee los JSON exportados en modo
 * lectura; no toca ninguna base.
 */

require 'C:/xampp/htdocs/Portal4N/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = __DIR__ . '/sep';
$acuerdos = json_decode(file_get_contents("$dir/lcl_acuerdos.json"), true);
$reglas = json_decode(file_get_contents("$dir/lcl_reglas.json"), true);
$dias = json_decode(file_get_contents("$dir/lcl_dias.json"), true);
$salida = 'C:/Users/elias/Downloads/Base_Acuerdos_202609.xlsx';

$libro = new Spreadsheet();

/* Hoja Calendario */
$cal = $libro->getActiveSheet();
$cal->setTitle('Calendario');
$cal->setCellValue('A1', 'Calendario septiembre 2026');
$cal->fromArray(['Fecha', 'Feriado'], null, 'A2');
$cal->fromArray(['Día', 'Días del mes'], null, 'E2');
$cal->fromArray(['Servicio', 'Días a pagar'], null, 'H2');

$conteo = array_fill(1, 7, 0);
foreach ($dias as $i => $dia) {
    $fila = $i + 3;
    $fecha = new DateTimeImmutable($dia['fecha']);
    $cal->setCellValue("A$fila", ExcelDate::PHPToExcel($fecha));
    $cal->getStyle("A$fila")->getNumberFormat()->setFormatCode('dd-mm-yyyy');
    if ((int) $dia['es_feriado'] === 1) {
        $cal->setCellValue("B$fila", 'x');
    } else {
        $conteo[(int) $fecha->format('N')]++;
    }
}

$nombres = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
foreach ($nombres as $n => $nombre) {
    $cal->setCellValue('E' . ($n + 2), $nombre);
    $cal->setCellValue('F' . ($n + 2), $conteo[$n]);
}

foreach ($reglas as $i => $regla) {
    $fila = $i + 3;
    $cal->setCellValueExplicit("H$fila", $regla['servicio'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    if ($regla['modo'] === 'fijo') {
        $cal->setCellValue("I$fila", (int) $regla['cantidad_fija']);
    } else {
        $semana = json_decode($regla['dias_semana'], true);
        $cal->setCellValue("I$fila", '=+' . implode('+', array_map(fn ($d) => 'F' . ($d + 2), $semana)));
    }
}

/* Hoja Base Acuerdos */
$base = $libro->createSheet();
$base->setTitle('Base Acuerdos');
$base->fromArray([
    'Proveedor', 'RUT', 'Agencia', 'Tipo Servicio', 'Marca', 'Servicio', 'Costo',
    'Q Calendario', 'Q Inasistencia', 'Q Adicionales', 'Cantidad', 'Glosa Factor',
    'Factor', 'Total', 'Razon Social Cliente', 'Comerciante Pila', 'Rut Cliente',
    'Nombre Comercial', 'Empresa Mandante', 'Zona',
], null, 'A1');

foreach ($acuerdos as $i => $a) {
    $fila = $i + 2;
    $valores = [
        $a['proveedor_origen'], $a['rut_proveedor_origen'], $a['agencia'], $a['tipo_servicio'],
        $a['marca'], $a['servicio'], (int) $a['costo'], (int) $a['dias_calendario'],
        (int) $a['inasistencias'], (int) $a['adicionales'], (int) $a['cantidad'], $a['glosa_factor'],
        (int) $a['factor'], (int) $a['total'], $a['razon_social_cliente_origen'],
        $a['comerciante_pila_origen'], $a['rut_cliente_origen'], $a['nombre_comercial_origen'],
        $a['empresa_mandante'], $a['zona'],
    ];
    foreach ($valores as $c => $valor) {
        $celda = [$c + 1, $fila];
        if (is_int($valor)) {
            $base->setCellValue($celda, $valor);
        } elseif ($valor !== null) {
            $base->setCellValueExplicit($celda, (string) $valor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }
}

(new Xlsx($libro))->save($salida);

echo 'Listo: ' . $salida . PHP_EOL;
echo count($acuerdos) . ' acuerdos, total ' . array_sum(array_column($acuerdos, 'total')) . PHP_EOL;
echo count($reglas) . ' servicios en la matriz, ' . count($dias) . ' días, '
    . count(array_filter($dias, fn ($d) => (int) $d['es_feriado'] === 1)) . ' feriados' . PHP_EOL;
