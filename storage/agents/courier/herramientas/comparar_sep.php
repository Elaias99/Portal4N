<?php
// Cruce bulto a bulto: resultado de la prueba en seco (p4n_nuevo.tsv) contra
// lo que LogisticaCL pagó en 202608 (lcl_periodo.tsv). Sólo lee archivos.
$dir = __DIR__ . '/sep';
$lcl = [];
foreach (file("$dir/lcl_periodo.tsv", FILE_IGNORE_NEW_LINES) as $l) {
    [$seg, $tipo, $cond, $valor, $rut] = array_pad(explode("\t", rtrim($l, "\r")), 5, '');
    $lcl[strtoupper($seg)] = ['tipo' => $tipo === 'Variable' ? 'Variables' : $tipo, 'paga' => $cond === 'SI', 'valor' => (int) $valor, 'rut' => $rut];
}
$f = fn ($n) => number_format((int) $n, 0, ',', '.');
$g = [];
$sumar = function ($k, $tipo, $dv) use (&$g) {
    $g[$tipo][$k]['n'] = ($g[$tipo][$k]['n'] ?? 0) + 1;
    $g[$tipo][$k]['dv'] = ($g[$tipo][$k]['dv'] ?? 0) + $dv;
};
$vistos = [];
$ejemplos = [];
foreach (file("$dir/p4n_nuevo.tsv", FILE_IGNORE_NEW_LINES) as $l) {
    [$seg, $tipo, $estado, $valor, $rut, $motivo, $tabla] = array_pad(explode("\t", $l), 7, '');
    $seg = strtoupper($seg);
    $vistos[$seg] = true;
    $paga = $estado === 'PAGAR';
    $o = $lcl[$seg] ?? null;
    $t = $paga ? $tipo : ($o['tipo'] ?? $tipo);
    if ($o === null) {
        if ($paga) $sumar('P4N paga · no está en su carga', $t, (int) $valor);
        continue;
    }
    if ($paga && $o['paga']) {
        if ((int) $valor !== $o['valor']) $sumar('ambos pagan · distinto valor', $t, (int) $valor - $o['valor']);
        elseif ($rut !== $o['rut']) $sumar('ambos pagan · distinto RUT', $t, 0);
        else $sumar('ambos pagan igual', $t, 0);
    } elseif ($paga) {
        $sumar('sólo P4N paga', $t, (int) $valor);
        $ejemplos['sólo P4N paga'][] = $seg;
    } elseif ($o['paga']) {
        $sumar("sólo LCL paga · P4N: $motivo", $t, -$o['valor']);
        $ejemplos["sólo LCL paga · P4N: $motivo"][] = $seg;
    }
}
foreach ($lcl as $seg => $o) {
    if (! isset($vistos[$seg]) && $o['paga']) $sumar('LCL paga · no está en P4N', $o['tipo'], -$o['valor']);
}
foreach ($g as $tipo => $grupos) {
    echo "\n== $tipo\n";
    uasort($grupos, fn ($a, $b) => abs($b['dv']) <=> abs($a['dv']) ?: $b['n'] <=> $a['n']);
    $neto = 0;
    foreach ($grupos as $k => $v) {
        printf("  %-48s %7s  %s\n", $k, $f($v['n']), ($v['dv'] >= 0 ? '+' : '-') . '$' . $f(abs($v['dv'])));
        $neto += $v['dv'];
    }
    echo "  diferencia neta: " . ($neto >= 0 ? '+' : '-') . '$' . $f(abs($neto)) . "\n";
}
foreach ($ejemplos as $k => $segs) file_put_contents("$dir/ej_" . preg_replace('/[^a-z0-9]+/i', '_', $k) . '.txt', implode("\n", $segs));
