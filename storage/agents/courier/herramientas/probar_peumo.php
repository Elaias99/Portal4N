<?php
// Sólo lectura: pasa los bultos Peumo de septiembre de LogisticaCL (en su orden) por CourierPeumo
// con las tarifas del Excel, y compara el valor con lo que pagó LogisticaCL.
require 'C:/xampp/htdocs/Portal4N/vendor/autoload.php';
$app = require 'C:/xampp/htdocs/Portal4N/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Services\Courier\CourierLlavesService;
use App\Services\Courier\CourierPeumo;
$filas = app(CourierLlavesService::class)->leer('C:/Users/elias/Downloads/Llaves_Courier_202608.xlsx');
$peumo = CourierPeumo::desdeFilas($filas['peumo']);
$bultos = []; $lcl = [];
foreach (file(__DIR__ . '/llaves/peumo_lcl_202609.tsv', FILE_IGNORE_NEW_LINES) as $l) {
    [$id, $guia, $comuna, $cond, $valor] = array_pad(explode("\t", rtrim($l, "\r")), 5, '');
    $bultos[] = (object) ['id' => (int) $id, 'guia_despacho' => $guia, 'comuna_destino' => $comuna];
    $lcl[(int) $id] = ['cond' => $cond, 'valor' => $valor === '' ? null : (int) $valor];
}
$r = $peumo->valores($bultos);
$iguales = $distintos = $sumaP4N = $sumaLCL = 0; $motivosEnSI = [];
foreach ($lcl as $id => $o) {
    if ($o['cond'] !== 'SI') continue;
    $sumaLCL += $o['valor'];
    $x = $r[$id];
    if (isset($x['motivo'])) { $motivosEnSI[$x['motivo']] = ($motivosEnSI[$x['motivo']] ?? 0) + 1; continue; }
    $sumaP4N += $x['valor'];
    $x['valor'] === $o['valor'] ? $iguales++ : $distintos++;
}
$f = fn ($n) => number_format($n, 0, ',', '.');
echo "Pagados por LogisticaCL: " . $f(count(array_filter($lcl, fn ($o) => $o['cond'] === 'SI'))) . " bultos, \${$f($sumaLCL)}\n";
echo "Portal4N sobre esos mismos: iguales {$f($iguales)}, distinto valor {$f($distintos)}, suma \${$f($sumaP4N)}\n";
echo "Sin valor en Portal4N: " . json_encode($motivosEnSI) . "\n";
