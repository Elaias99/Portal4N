<?php

/*
 * Arma los archivos de septiembre de Ruta CV, Servicios, Visitas, Especiales
 * y Apoyo Alza con el formato de las plantillas de LogisticaCL, desde los
 * JSON exportados en modo lectura. Al final de cada plantilla se agregan
 * columnas opcionales (RUT Proveedor, Días, Zona) cuando la plantilla no
 * las trae.
 */

require 'C:/xampp/htdocs/Portal4N/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = __DIR__ . '/sep';
$destino = 'C:/Users/elias/Downloads';
$leer = fn ($k) => json_decode(file_get_contents("$dir/x_$k.json"), true);

function escribir($hoja, int $col, int $fila, $valor): void
{
    $celda = Coordinate::stringFromColumnIndex($col) . $fila;
    if ($valor === null || $valor === '') {
        return;
    }
    if (is_int($valor) || is_float($valor)) {
        $hoja->setCellValue($celda, $valor);
    } elseif (is_string($valor) && str_starts_with($valor, '=')) {
        $hoja->setCellValue($celda, $valor);
    } else {
        $hoja->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);
    }
}

function guardar(Spreadsheet $libro, string $ruta, string $resumen): void
{
    (new Xlsx($libro))->save($ruta);
    echo basename($ruta) . ': ' . $resumen . PHP_EOL;
}

function fechaExcel(?string $fecha)
{
    return $fecha ? ExcelDate::PHPToExcel(new DateTimeImmutable(substr($fecha, 0, 10))) : null;
}

/* ---------- Ruta CV ---------- */
$libro = new Spreadsheet();
$h = $libro->getActiveSheet();
$h->setTitle('Base Ruta CV');
$enc = ['Periodo', 'Proceso', 'Zona', 'Servicio', 'Frecuencia', 'Facturador', 'Usuario', 'Detalle-Ruta', 'Comuna', 'Producto', 'Valor', 'Agente'];
foreach ($enc as $i => $t) escribir($h, $i + 1, 1, $t);
for ($d = 1; $d <= 31; $d++) escribir($h, $d + 12, 1, $d);
foreach (['Días', 'Inasistencia', 'Total', 'Observación', 'RUT Proveedor'] as $i => $t) escribir($h, 44 + $i, 1, $t);
$total = 0;
foreach ($leer('rutas') as $i => $r) {
    $f = $i + 2;
    $dias = json_decode($r['dias'], true) ?: [];
    $vals = [$r['periodo'], $r['proceso'], $r['mz'], $r['servicio'], $r['frecuencia'], $r['facturador'], $r['usuario'],
        $r['detalle_ruta'], $r['comuna'], $r['producto'], (int) $r['valor'], $r['agente']];
    foreach ($vals as $c => $v) escribir($h, $c + 1, $f, $v);
    foreach ($dias as $d) escribir($h, $d + 12, $f, 'x');
    escribir($h, 44, $f, "=COUNTIF(M{$f}:AQ{$f},\"x\")");
    escribir($h, 45, $f, (int) $r['inasistencia']);
    escribir($h, 46, $f, $r['tipo_cobro'] === 'fijo' ? (int) $r['monto_fijo'] : "=MAX(0,AR{$f}-AS{$f})*K{$f}");
    escribir($h, 47, $f, $r['observacion']);
    escribir($h, 48, $f, $r['prov_rut']);
    $total += (int) $r['total_mensual'];
}
guardar($libro, "$destino/Base_Ruta_CV_202609.xlsx", count($leer('rutas')) . " rutas, total $total");

/* ---------- Servicios ---------- */
$libro = new Spreadsheet();
$h = $libro->getActiveSheet();
$h->setTitle('Base_Servicios');
$enc = ['Zona', 'Tipo de Pago', 'Seguimiento paquete', 'Fecha Carga', 'Dirección', 'Numero destino', 'Depto destino',
    'Comuna Destino', 'Razón Social Cliente', 'RUT Cliente', 'Servicio', 'Peso', 'Estado del envío', 'Valor final',
    'Operador', 'Usuario', 'Periodo', 'Usuario2', 'Transportista', 'Razón Social', 'RUT', 'Empresa'];
foreach ($enc as $i => $t) escribir($h, $i + 1, 1, $t);
$total = 0;
foreach ($leer('servicios') as $i => $s) {
    $f = $i + 2;
    $vals = [$s['mz'], $s['tipo_pago'], $s['seguimiento_paquete'], fechaExcel($s['fecha_carga']), $s['direccion'],
        $s['numero_destino'], $s['depto_destino'], $s['comuna_destino'], $s['cliente_origen'], $s['rut_cliente_origen'],
        $s['servicio'], is_numeric($s['peso']) ? $s['peso'] + 0 : $s['peso'], $s['estado_envio'], (int) $s['valor_final'],
        $s['operador'], $s['usuario'], $s['periodo_origen'], $s['usuario2'], $s['transportista'],
        $s['razon_social_proveedor_origen'], $s['prov_rut'], $s['empresa']];
    foreach ($vals as $c => $v) escribir($h, $c + 1, $f, $v);
    $h->getStyle("D{$f}")->getNumberFormat()->setFormatCode('dd-mm-yyyy');
    $total += (int) $s['valor_final'];
}
guardar($libro, "$destino/Base_Servicios_202609.xlsx", count($leer('servicios')) . " servicios, total $total");

/* ---------- Visitas ---------- */
$libro = new Spreadsheet();
$h = $libro->getActiveSheet();
$h->setTitle('Base_Visitas');
$enc = ['NuevoAgente', 'Local', 'Nombre del Local', 'Direccion', 'Comuna', 'Frecuencia', 'SLA Operador desde RM (Paq)',
    'SLA Cliente desde RM (Paq)', 'SLA desde Locales a CD', 'Estatus', 'Razón social proveedor', 'Nombre de pila proveedor',
    'RUT proveedor', 'Valor x Dia', 'Cliente', 'Rut Cliente', 'Comerciante (Pila)', 'Días', 'Zona'];
foreach ($enc as $i => $t) escribir($h, $i + 1, 1, $t);
$total = 0;
foreach ($leer('visitas') as $i => $v) {
    $f = $i + 2;
    $vals = [$v['agente_original'], $v['local'], $v['nombre_local'], $v['direccion'], $v['comuna'], $v['frecuencia'],
        $v['sla_operador'], $v['sla_cliente'], $v['sla_local_cd'], $v['estatus_origen'], $v['razon_social_proveedor_origen'],
        $v['nombre_pila_proveedor_origen'], $v['prov_rut'], (int) $v['valor_dia'], $v['razon_social_cliente_origen'],
        $v['rut_cliente_origen'], $v['comerciante_pila_origen'], implode(',', json_decode($v['dias'], true) ?: []), $v['mz']];
    foreach ($vals as $c => $x) escribir($h, $c + 1, $f, $x);
    $total += (int) $v['total_mensual'];
}
guardar($libro, "$destino/Base_Visitas_202609.xlsx", count($leer('visitas')) . " visitas, total $total");

/* ---------- Especiales ---------- */
$libro = new Spreadsheet();
$h = $libro->getActiveSheet();
$h->setTitle('Especiales');
$enc = ['Fecha', 'Usuario Ingresa', 'Autoriza', 'Agente', 'Zona / Tipo', 'ID', 'Localidad', 'Cliente', 'Descripción', 'Monto', 'RUT Proveedor', 'Zona'];
foreach ($enc as $i => $t) escribir($h, $i + 1, 1, $t);
$total = 0;
foreach ($leer('especiales') as $i => $e) {
    $f = $i + 2;
    $vals = [fechaExcel($e['fecha']), $e['usuario_ingresa'], $e['autoriza'], $e['agente'], $e['zona_tipo'],
        $e['codigo_seguimiento'], $e['localidad'], $e['cliente'], $e['descripcion'], (int) $e['monto'], $e['prov_rut'], $e['mz']];
    foreach ($vals as $c => $x) escribir($h, $c + 1, $f, $x);
    $h->getStyle("A{$f}")->getNumberFormat()->setFormatCode('dd-mm-yyyy');
    $total += (int) $e['monto'];
}
guardar($libro, "$destino/Especiales_202609.xlsx", count($leer('especiales')) . " especiales, total $total");

/* ---------- Apoyo Alza ---------- */
$libro = new Spreadsheet();
$h = $libro->getActiveSheet();
$h->setTitle('Apoyo Alza');
$enc = ['Razón Social Cliente', 'RUT Cliente', 'Proceso', 'Servicio de Acuerdo', 'Factor', 'Porcentaje', 'Monto', 'Empresa Mandante', 'Agencia', 'Zona'];
foreach ($enc as $i => $t) escribir($h, $i + 1, 1, $t);
$total = 0;
foreach ($leer('apoyo') as $i => $a) {
    $f = $i + 2;
    $vals = [$a['proveedor_origen'], $a['prov_rut'], $a['proceso_base'], $a['servicio_acuerdo'], $a['factor'],
        $a['porcentaje'] === null ? null : (float) $a['porcentaje'], $a['monto_dia'] === null ? null : (int) $a['monto_dia'],
        $a['empresa_mandante'], $a['agencia'], $a['mz']];
    foreach ($vals as $c => $x) escribir($h, $c + 1, $f, $x);
    $total += (int) $a['monto_apoyo'];
}
guardar($libro, "$destino/Apoyo_Alza_202609.xlsx", count($leer('apoyo')) . " filas, total LogisticaCL $total");
