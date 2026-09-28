<?php

namespace App\Console\Commands;

use App\Imports\Courier\BaseClImport;
use App\Imports\Courier\FiltroColumnas;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierCalculoService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/*
 * Compara bulto por bulto el resultado del sistema contra la hoja BaseCL
 * de la planilla de Operaciones.
 *
 * Por qué bulto por bulto y no por totales: la planilla se armó con una
 * descarga de Geolice anterior a la que tenemos, así que los totales no
 * son comparables. Sobre los bultos que están en los dos lados sí lo son,
 * y los que sólo están en uno se informan aparte, con su monto.
 *
 * Este comando SÓLO LEE. No escribe ni una fila.
 */
class CourierCompararPlanilla extends Command
{
    protected $signature = 'courier:comparar-planilla
        {archivo : Planilla mensual de Operaciones (AAAAMM_4N_COURIER_RESPALDOS.xlsx)}
        {--periodo= : Período de pago AAAAMM (ej. 202608)}
        {--hoja=BaseCL : Hoja con el resultado final de la planilla}
        {--ejemplos=10 : Cuántos bultos mostrar como ejemplo en cada diferencia}';

    protected $description = 'Compara el cálculo del sistema contra la hoja BaseCL de la planilla de Operaciones, bulto por bulto. No escribe nada.';

    /* En Lanas la planilla fija el peso en 1, así que el peso no se compara. */
    private const TIPO_LANAS = 'Lanas';

    public function handle(): int
    {
        $archivo = (string) $this->argument('archivo');
        $codigo = (string) $this->option('periodo');
        $hoja = (string) $this->option('hoja');
        $tope = max(1, (int) $this->option('ejemplos'));

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $codigo)) {
            $this->error('Indica el período con --periodo=AAAAMM (ej. --periodo=202608).');

            return self::FAILURE;
        }

        $periodo = CourierPeriodo::where('codigo', $codigo)->first();

        if (! $periodo) {
            $this->error("El período {$codigo} no existe todavía. Primero importa la descarga de Geolice.");

            return self::FAILURE;
        }

        // El libro pesa decenas de MB; BaseCL sola pasa las 25 mil filas.
        set_time_limit(0);
        ini_set('memory_limit', '4096M');

        $this->info("Período {$periodo->nombre}. Leyendo la hoja {$hoja} de " . basename($archivo) . '…');

        try {
            $lector = new BaseClImport;
            $planilla = $lector->leer($this->leerHoja($archivo, $hoja, 16));
        } catch (\Throwable $e) {
            $this->error('No se pudo leer la planilla: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->mostrar('La planilla dice', [
            'Filas leídas' => $this->n($lector->resumen['filas_leidas']),
            'Bultos distintos' => $this->n($lector->resumen['bultos']),
            'Filas sin código' => $this->n($lector->resumen['sin_codigo']),
            'Repetidos (se tomó el primero)' => $this->n($lector->resumen['repetidos']),
            'Valor total' => '$' . $this->n($lector->resumen['valor_total']),
        ]);

        $sistema = $this->bultosDelSistema($periodo->id);

        if ($sistema === []) {
            $this->error('El período no tiene bultos cargados. Importa Geolice y corre courier:calcular.');

            return self::FAILURE;
        }

        $this->informar($this->comparar($planilla, $sistema), $tope);

        return self::SUCCESS;
    }

    /*
     * Lo que calculó el sistema, con el nombre del agente resuelto.
     * Query builder y no Eloquent: son decenas de miles de filas y sólo
     * se necesitan nueve columnas.
     *
     * @return array<string,array<string,mixed>>
     */
    private function bultosDelSistema(int $periodoId): array
    {
        $bultos = [];

        DB::table('courier_bultos as b')
            ->leftJoin('courier_agentes as a', 'a.id', '=', 'b.courier_agente_id')
            ->where('b.courier_periodo_id', $periodoId)
            ->select([
                'b.seguimiento',
                'b.peso_pago',
                'b.valor',
                'b.estado_pago',
                'b.motivo',
                'b.zona',
                'b.tipo_pago',
                'b.calculado_at',
                'a.nombre as agente',
            ])
            ->orderBy('b.id')
            ->chunk(5000, function (Collection $filas) use (&$bultos) {
                foreach ($filas as $fila) {
                    $bultos[mb_strtoupper(trim((string) $fila->seguimiento))] = [
                        'peso' => (int) $fila->peso_pago,
                        'valor' => (int) $fila->valor,
                        'estado_pago' => (string) $fila->estado_pago,
                        'motivo' => (string) $fila->motivo,
                        'zona' => (string) $fila->zona,
                        'tipo_pago' => (string) $fila->tipo_pago,
                        'agente' => (string) $fila->agente,
                        'calculado' => $fila->calculado_at !== null,
                    ];
                }
            });

        return $bultos;
    }

    /*
     * Reparte los bultos en grupos. El orden de las preguntas importa:
     * un bulto cae en un solo grupo, y primero se pregunta lo más grave
     * (pagarle a otro operador) antes que lo más leve (el peso).
     */
    private function comparar(array $planilla, array $sistema): array
    {
        $grupos = [
            'iguales' => [],
            'distinto_operador' => [],
            'distinto_valor' => [],
            'distinto_peso' => [],
            'planilla_paga_sistema_no' => [],
            'no_esta_en_el_sistema' => [],
            'sistema_paga_planilla_no' => [],
        ];

        $montos = array_fill_keys(array_keys($grupos), ['planilla' => 0, 'sistema' => 0]);
        $motivos = [];
        $sinCalcular = 0;

        foreach ($planilla as $seguimiento => $p) {
            $s = $sistema[$seguimiento] ?? null;

            if ($s === null) {
                $grupos['no_esta_en_el_sistema'][] = [$seguimiento, $p['operador'] ?: '—', $this->n($p['valor'])];
                $montos['no_esta_en_el_sistema']['planilla'] += $p['valor'];

                continue;
            }

            if (! $s['calculado']) {
                $sinCalcular++;
            }

            if ($s['estado_pago'] !== 'PAGAR') {
                $motivo = $s['motivo'] ?: 'sin motivo';
                $motivos[$motivo] = ($motivos[$motivo] ?? 0) + 1;

                $grupos['planilla_paga_sistema_no'][] = [
                    $seguimiento,
                    $this->n($p['valor']),
                    CourierCalculoService::MOTIVOS[$motivo] ?? $motivo,
                ];
                $montos['planilla_paga_sistema_no']['planilla'] += $p['valor'];

                continue;
            }

            /* Los dos lados pagan el bulto: ahora se comparan los datos. */
            $mismoOperador = $this->igual($p['operador'], $s['agente']);
            $mismoValor = $p['valor'] === $s['valor'];
            $esLanas = $this->igual($p['tipo_pago'], self::TIPO_LANAS)
                || $this->igual($s['tipo_pago'], self::TIPO_LANAS);
            $mismoPeso = $esLanas || $p['peso'] === $s['peso'];

            if (! $mismoOperador) {
                $grupo = 'distinto_operador';
                $detalle = [$seguimiento, $p['operador'] ?: '—', $s['agente'] ?: '—', $this->n($p['valor']), $this->n($s['valor'])];
            } elseif (! $mismoValor) {
                $grupo = 'distinto_valor';
                $detalle = [$seguimiento, $p['peso'], $s['peso'], $this->n($p['valor']), $this->n($s['valor']), $this->n($s['valor'] - $p['valor'])];
            } elseif (! $mismoPeso) {
                $grupo = 'distinto_peso';
                $detalle = [$seguimiento, $p['peso'], $s['peso'], $this->n($p['valor'])];
            } else {
                $grupo = 'iguales';
                $detalle = [$seguimiento, $p['peso'], $this->n($p['valor'])];
            }

            $grupos[$grupo][] = $detalle;
            $montos[$grupo]['planilla'] += $p['valor'];
            $montos[$grupo]['sistema'] += $s['valor'];
        }

        /*
         * Lo que el sistema paga y la planilla no tiene. Acá están los
         * bultos que su descarga de Geolice no alcanzó a ver: no son un
         * error, son plata que se quedó sin pagar.
         */
        foreach ($sistema as $seguimiento => $s) {
            if ($s['estado_pago'] !== 'PAGAR' || isset($planilla[$seguimiento])) {
                continue;
            }

            $grupos['sistema_paga_planilla_no'][] = [
                $seguimiento,
                $s['agente'] ?: 'sin agente',
                $s['tipo_pago'] ?: '—',
                $this->n($s['valor']),
            ];
            $montos['sistema_paga_planilla_no']['sistema'] += $s['valor'];
        }

        arsort($motivos);

        return [
            'grupos' => $grupos,
            'montos' => $montos,
            'motivos' => $motivos,
            'sin_calcular' => $sinCalcular,
        ];
    }

    private function informar(array $c, int $tope): void
    {
        $grupos = $c['grupos'];
        $montos = $c['montos'];

        if ($c['sin_calcular'] > 0) {
            $this->warn('Atención: ' . $this->n($c['sin_calcular']) . ' bultos de la planilla existen en el sistema pero todavía no se han calculado. Corre courier:calcular antes de sacar conclusiones.');
        }

        $titulos = [
            'iguales' => 'Iguales en todo (operador, valor y peso)',
            'distinto_peso' => 'Mismo valor, distinto peso',
            'distinto_valor' => 'Distinto valor',
            'distinto_operador' => 'Distinto operador',
            'planilla_paga_sistema_no' => 'La planilla lo paga y el sistema no',
            'no_esta_en_el_sistema' => 'Está en la planilla y no existe en el sistema',
            'sistema_paga_planilla_no' => 'El sistema lo paga y la planilla no lo tiene',
        ];

        $filas = [];

        foreach ($titulos as $clave => $titulo) {
            $filas[] = [
                $titulo,
                $this->n(count($grupos[$clave])),
                $montos[$clave]['planilla'] > 0 ? '$' . $this->n($montos[$clave]['planilla']) : '—',
                $montos[$clave]['sistema'] > 0 ? '$' . $this->n($montos[$clave]['sistema']) : '—',
            ];
        }

        $this->newLine();
        $this->table(['Grupo', 'Bultos', 'Valor planilla', 'Valor sistema'], $filas);

        $enComun = count($grupos['iguales']) + count($grupos['distinto_operador'])
            + count($grupos['distinto_valor']) + count($grupos['distinto_peso']);

        if ($enComun > 0) {
            $coincidencia = round(count($grupos['iguales']) / $enComun * 100, 1);

            $this->info(
                'De los ' . $this->n($enComun) . ' bultos que ambos pagan, '
                . $this->n(count($grupos['iguales'])) . " coinciden en todo: {$coincidencia}%."
            );
        }

        if ($c['motivos'] !== []) {
            $this->newLine();
            $this->mostrar(
                'Por qué el sistema no paga lo que la planilla sí',
                collect($c['motivos'])
                    ->mapWithKeys(fn ($v, $k) => [CourierCalculoService::MOTIVOS[$k] ?? $k => $this->n($v)])
                    ->all()
            );
        }

        $encabezados = [
            'distinto_operador' => ['Bulto', 'Operador planilla', 'Agente sistema', 'Valor planilla', 'Valor sistema'],
            'distinto_valor' => ['Bulto', 'Kilos planilla', 'Kilos sistema', 'Valor planilla', 'Valor sistema', 'Diferencia'],
            'distinto_peso' => ['Bulto', 'Kilos planilla', 'Kilos sistema', 'Valor'],
            'planilla_paga_sistema_no' => ['Bulto', 'Valor planilla', 'Motivo del sistema'],
            'no_esta_en_el_sistema' => ['Bulto', 'Operador planilla', 'Valor planilla'],
            'sistema_paga_planilla_no' => ['Bulto', 'Agente', 'Tipo de pago', 'Valor'],
        ];

        foreach ($encabezados as $clave => $columnas) {
            if ($grupos[$clave] === []) {
                continue;
            }

            $this->newLine();
            $this->line("<comment>{$titulos[$clave]}</comment> — " . $this->n(count($grupos[$clave])) . ' bultos, se muestran hasta ' . $this->n($tope) . '.');
            $this->table($columnas, array_slice($grupos[$clave], 0, $tope));
        }
    }

    /*
     * Carga una sola hoja, y sólo sus primeras columnas.
     *
     * Igual que courier:importar-mes: las celdas con fórmula llegan como
     * texto ("=+F2"), así que se reemplazan por el último valor que dejó
     * calculado Excel.
     */
    private function leerHoja(string $archivo, string $hoja, int $hastaColumna): Collection
    {
        $lector = IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $lector->setLoadSheetsOnly($hoja);
        $lector->setReadFilter(new FiltroColumnas($hastaColumna));

        $libro = $lector->load($archivo);
        $pagina = $libro->getActiveSheet();

        $filas = $pagina->toArray(null, false, false, false);

        foreach ($filas as $i => $fila) {
            foreach ($fila as $j => $valor) {
                if (is_string($valor) && str_starts_with($valor, '=')) {
                    $columna = Coordinate::stringFromColumnIndex($j + 1);
                    $filas[$i][$j] = $pagina->getCell($columna . ($i + 1))->getOldCalculatedValue();
                }
            }
        }

        $libro->disconnectWorksheets();
        unset($libro, $lector, $pagina);
        gc_collect_cycles();

        return collect($filas);
    }

    /*
     * Comparación de textos al estilo del BUSCARV: sin distinguir
     * mayúsculas ni espacios de los extremos, pero sin tocar tildes.
     */
    private function igual(?string $a, ?string $b): bool
    {
        return mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));
    }

    private function mostrar(string $titulo, array $datos): void
    {
        $this->table(
            [$titulo, ''],
            collect($datos)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );
    }

    private function n(int|float $valor): string
    {
        return number_format((int) $valor, 0, ',', '.');
    }
}