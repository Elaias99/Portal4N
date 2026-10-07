<?php
/*
 * Arma Downloads/Mes_202609.xlsx para courier:importar-mes, con el mismo
 * formato de hojas que la planilla de Operaciones, desde lo que
 * LogisticaCL tiene para septiembre (exportado antes en modo lectura).
 *   PesoReal:   A seguimiento, B kilos, D fecha de proceso
 *   Blue:       B seguimiento, G DESCONTAR
 *   especiales: F seguimiento, K DESCONTAR
 */
require 'C:/xampp/htdocs/Portal4N/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = __DIR__ . '/sep';
$leer = fn ($f) => array_map(fn ($l) => explode("\t", rtrim($l, "\r")), array_filter(file("$dir/$f", FILE_IGNORE_NEW_LINES), fn ($l) => trim($l) !== ''));
$libro = new Spreadsheet();

$hoja = $libro->getActiveSheet();
$hoja->setTitle('PesoReal');
$hoja->fromArray(['Seguimiento', 'Kilos', '', 'Fecha proceso'], null, 'A1');
$fila = 2;
foreach ($leer('pesoreal.tsv') as [$seg, $kilos, $fecha]) {
    $hoja->setCellValueExplicit("A$fila", $seg, DataType::TYPE_STRING);
    $hoja->setCellValue("B$fila", (int) $kilos);
    $hoja->setCellValueExplicit("D$fila", $fecha, DataType::TYPE_STRING);
    $fila++;
}
echo 'PesoReal ' . ($fila - 2) . "\n";

foreach (['Blue' => ['blue.tsv', 'B', 'G'], 'especiales' => ['especiales.tsv', 'F', 'K']] as $titulo => [$archivo, $colSeg, $colMarca]) {
    $hoja = $libro->createSheet();
    $hoja->setTitle($titulo);
    $hoja->setCellValue("{$colSeg}1", 'Seguimiento');
    $hoja->setCellValue("{$colMarca}1", 'Pago');
    $fila = 2;
    foreach ($leer($archivo) as [$seg]) {
        $hoja->setCellValueExplicit("$colSeg$fila", $seg, DataType::TYPE_STRING);
        $hoja->setCellValue("$colMarca$fila", 'DESCONTAR');
        $fila++;
    }
    echo "$titulo " . ($fila - 2) . "\n";
}

$libro->setActiveSheetIndex(0);
(new Xlsx($libro))->save('C:/Users/elias/Downloads/Mes_202609.xlsx');
echo "Guardado: Downloads/Mes_202609.xlsx\n";
