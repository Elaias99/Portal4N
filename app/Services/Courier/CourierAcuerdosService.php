<?php

namespace App\Services\Courier;

use App\Models\CourierAcuerdo;
use App\Models\CourierAcuerdoDia;
use App\Models\CourierAcuerdoRegla;
use App\Models\CourierPeriodo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/*
 * Acuerdos: pagos fijos del mes a cada proveedor, igual que en
 * LogisticaCL. Se cargan desde Base_Acuerdos.xlsx:
 *
 *  - Hoja Calendario: los días del mes desde A3 (columna B marca los
 *    feriados con x) y la matriz de servicios en H/I. En I va un número
 *    (días fijos) o una fórmula que suma celdas F3…F9, una por día de la
 *    semana de lunes a domingo.
 *  - Hoja Base Acuerdos: una fila por acuerdo, con las 19 columnas de la
 *    plantilla. Una columna 20 «Zona» es opcional.
 *
 * total = costo × (días del calendario − inasistencias + adicionales) × factor
 */
class CourierAcuerdosService
{
    private const ENCABEZADOS = [
        'proveedor', 'rut', 'agencia', 'tiposervicio', 'marca', 'servicio', 'costo',
        'qcalendario', 'qinasistencia', 'qadicionales', 'cantidad', 'glosafactor',
        'factor', 'total', 'razonsocialcliente', 'comerciantepila', 'rutcliente',
        'nombrecomercial', 'empresamandante',
    ];

    /* Están en la matriz del calendario, pero se calculan en Apoyo Alza. */
    private const NO_SON_ACUERDOS = ['apoyo alza', 'agencia apoyo alza'];

    private const ZONAS = ['RM', 'Regiones'];

    public function __construct(
        private readonly CourierProveedoresPorRut $proveedores,
        private readonly CourierProcesosService $procesos
    ) {
    }

    /*
     * Reemplaza los acuerdos del período con los del archivo y los
     * calcula. Lanza una excepción con la fila y el motivo ante el primer
     * dato que no se puede leer, sin guardar nada.
     */
    public function importar(string $ruta, string $nombreArchivo, CourierPeriodo $periodo): array
    {
        $lector = IOFactory::createReaderForFile($ruta);
        $lector->setLoadSheetsOnly(['Calendario', 'Base Acuerdos']);
        $libro = $lector->load($ruta);

        try {
            $calendario = $libro->getSheetByName('Calendario');
            $base = $libro->getSheetByName('Base Acuerdos');

            if ($calendario === null || $base === null) {
                throw new \RuntimeException('El archivo debe tener las hojas «Calendario» y «Base Acuerdos».');
            }

            $encabezados = [];
            for ($columna = 1; $columna <= 19; $columna++) {
                $encabezados[] = $this->encabezado($base->getCell($this->celda($columna, 1))->getValue());
            }

            if ($encabezados !== self::ENCABEZADOS) {
                throw new \RuntimeException('Las columnas de «Base Acuerdos» no son las de la plantilla.');
            }

            $traeZona = $this->encabezado($base->getCell('T1')->getValue()) === 'zona';

            $dias = $this->dias($calendario, $periodo);
            $reglas = $this->reglas($calendario);

            $filas = [];
            $totalArchivo = 0;

            for ($linea = 2; $linea <= $base->getHighestDataRow(); $linea++) {
                $valores = [];
                for ($columna = 1; $columna <= 20; $columna++) {
                    $valores[] = $base->getCell($this->celda($columna, $linea))->getCalculatedValue();
                }

                if (collect(array_slice($valores, 0, 19))->every(fn ($v) => $v === null || trim((string) $v) === '')) {
                    continue;
                }

                $servicio = trim((string) $valores[5]);
                if ($servicio === '' || ! isset($reglas[$servicio])) {
                    throw new \RuntimeException("El servicio de la fila {$linea} no está en la matriz del Calendario.");
                }

                $nombreProveedor = trim((string) $valores[0]);
                if ($nombreProveedor === '') {
                    throw new \RuntimeException("Falta el proveedor en la fila {$linea}.");
                }

                $factor = $this->entero($valores[12] ?? 1, $linea, 'Factor');
                if ($factor < 1) {
                    throw new \RuntimeException("El factor de la fila {$linea} debe ser al menos 1.");
                }

                $rut = $this->texto($valores[1]);
                $proveedor = $this->proveedores->buscar($rut);
                $zona = $traeZona && in_array(trim((string) $valores[19]), self::ZONAS, true)
                    ? trim((string) $valores[19])
                    : ($proveedor['zona'] ?? null);

                $totalArchivo += (int) $valores[13];

                $filas[] = [
                    'fila_origen' => $linea,
                    'proveedor' => $nombreProveedor,
                    'rut_proveedor' => $rut,
                    'courier_proveedor_id' => $proveedor['id'] ?? null,
                    'zona' => $zona,
                    'agencia' => $this->texto($valores[2]),
                    'tipo_servicio' => $this->texto($valores[3]),
                    'marca' => $this->texto($valores[4]),
                    'servicio' => $servicio,
                    'costo' => $this->entero($valores[6], $linea, 'Costo'),
                    'inasistencias' => $this->entero($valores[8] ?? 0, $linea, 'Inasistencias'),
                    'adicionales' => $this->entero($valores[9] ?? 0, $linea, 'Adicionales'),
                    'glosa_factor' => $this->texto($valores[11]),
                    'factor' => $factor,
                    'razon_social_cliente' => $this->texto($valores[14]),
                    'comerciante' => $this->texto($valores[15]),
                    'rut_cliente' => $this->texto($valores[16]),
                    'nombre_comercial' => $this->texto($valores[17]),
                    'empresa_mandante' => $this->texto($valores[18]),
                ];
            }

            if ($filas === []) {
                throw new \RuntimeException('La hoja «Base Acuerdos» no tiene acuerdos.');
            }
        } finally {
            $libro->disconnectWorksheets();
        }

        return DB::transaction(function () use ($periodo, $nombreArchivo, $dias, $reglas, $filas, $totalArchivo) {
            CourierAcuerdo::delPeriodo($periodo->id)->delete();
            CourierAcuerdoRegla::delPeriodo($periodo->id)->delete();
            CourierAcuerdoDia::delPeriodo($periodo->id)->delete();

            $ahora = now();

            CourierAcuerdoDia::insert(array_map(fn ($dia) => [
                'courier_periodo_id' => $periodo->id,
                'fecha' => $dia['fecha'],
                'es_feriado' => $dia['es_feriado'],
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], $dias));

            foreach ($reglas as $servicio => $regla) {
                CourierAcuerdoRegla::create([
                    'courier_periodo_id' => $periodo->id,
                    'servicio' => $servicio,
                    'modo' => $regla['modo'],
                    'dias_semana' => $regla['dias_semana'],
                    'cantidad_fija' => $regla['cantidad_fija'],
                ]);
            }

            foreach (array_chunk($filas, 250) as $lote) {
                CourierAcuerdo::insert(array_map(fn ($fila) => $fila + [
                    'courier_periodo_id' => $periodo->id,
                    'archivo_origen' => $nombreArchivo,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ], $lote));
            }

            $calculo = $this->calcular($periodo);

            /* Lo que ganan en Acuerdos es base de Apoyo Alza. */
            $this->procesos->recalcularApoyo($periodo);

            return $calculo + [
                'servicios' => count($reglas),
                'dias' => count($dias),
                'feriados' => count(array_filter($dias, fn ($d) => $d['es_feriado'])),
                'total_archivo' => $totalArchivo,
                'sin_proveedor' => CourierAcuerdo::delPeriodo($periodo->id)->whereNull('courier_proveedor_id')->count(),
                'sin_zona' => CourierAcuerdo::delPeriodo($periodo->id)->whereNull('zona')->count(),
            ];
        });
    }

    /*
     * Días del calendario por servicio, cantidad y total de cada acuerdo.
     * Se puede repetir cuantas veces se quiera.
     */
    public function calcular(CourierPeriodo $periodo): array
    {
        $reglas = CourierAcuerdoRegla::delPeriodo($periodo->id)->get()->keyBy('servicio');

        /* Días hábiles del mes por día de la semana (1 = lunes … 7 = domingo). */
        $porDiaSemana = CourierAcuerdoDia::delPeriodo($periodo->id)
            ->where('es_feriado', false)
            ->get()
            ->countBy(fn (CourierAcuerdoDia $dia) => $dia->fecha->dayOfWeekIso);

        $acuerdos = 0;
        $total = 0;

        CourierAcuerdo::delPeriodo($periodo->id)
            ->chunkById(200, function ($filas) use ($reglas, $porDiaSemana, &$acuerdos, &$total) {
                foreach ($filas as $acuerdo) {
                    $regla = $reglas->get($acuerdo->servicio);

                    if ($regla === null) {
                        throw new \RuntimeException("No hay regla de calendario para «{$acuerdo->servicio}».");
                    }

                    $dias = $regla->modo === CourierAcuerdoRegla::FIJO
                        ? (int) $regla->cantidad_fija
                        : array_sum(array_map(fn (int $d) => (int) ($porDiaSemana[$d] ?? 0), $regla->dias_semana ?? []));

                    $cantidad = $dias - $acuerdo->inasistencias + $acuerdo->adicionales;

                    if ($cantidad < 0) {
                        throw new \RuntimeException("Las inasistencias superan los días del acuerdo de la fila {$acuerdo->fila_origen}.");
                    }

                    $acuerdo->update([
                        'dias_calendario' => $dias,
                        'cantidad' => $cantidad,
                        'total' => $acuerdo->costo * $cantidad * $acuerdo->factor,
                    ]);

                    $acuerdos++;
                    $total += $acuerdo->total;
                }
            });

        return ['acuerdos' => $acuerdos, 'total' => $total];
    }

    /* Los días del mes desde A3, que tienen que ser del período indicado. */
    private function dias(Worksheet $calendario, CourierPeriodo $periodo): array
    {
        $primero = $this->fecha($calendario->getCell('A3')->getCalculatedValue());

        if ($primero === null || $primero->day !== 1) {
            throw new \RuntimeException('La primera fecha del Calendario (A3) debe ser el día 1 del mes.');
        }

        if ($primero->format('Ym') !== $periodo->codigo) {
            throw new \RuntimeException("El Calendario es de {$primero->format('Ym')} y el período indicado es {$periodo->codigo}.");
        }

        $dias = [];
        for ($dia = 1; $dia <= $primero->daysInMonth; $dia++) {
            $marca = mb_strtolower(trim((string) $calendario->getCell('B' . ($dia + 2))->getCalculatedValue()));

            $dias[] = [
                'fecha' => $primero->day($dia)->toDateString(),
                'es_feriado' => in_array($marca, ['x', '1', 'si', 'sí'], true),
            ];
        }

        return $dias;
    }

    /*
     * La matriz de servicios: H = servicio, I = días. Un número es una
     * cantidad fija; una fórmula como =+F3+F5 son los lunes y miércoles
     * del mes (F3 = lunes … F9 = domingo). Una fórmula también puede
     * sumar la celda I de otro servicio para heredar sus días.
     *
     * @return array<string, array{modo: string, dias_semana: ?array, cantidad_fija: ?int}>
     */
    private function reglas(Worksheet $calendario): array
    {
        $crudas = [];

        for ($fila = 3; $fila <= $calendario->getHighestDataRow(); $fila++) {
            $servicio = trim((string) $calendario->getCell("H{$fila}")->getValue());

            if ($servicio === '' || in_array(mb_strtolower($servicio), self::NO_SON_ACUERDOS, true)) {
                continue;
            }

            if (isset($crudas[$servicio])) {
                throw new \RuntimeException("El servicio «{$servicio}» aparece dos veces en la matriz del Calendario.");
            }

            $crudas[$servicio] = ['fila' => $fila, 'valor' => $calendario->getCell("I{$fila}")->getValue()];
        }

        if ($crudas === []) {
            throw new \RuntimeException('La matriz de servicios del Calendario está vacía.');
        }

        $porFila = [];
        foreach ($crudas as $servicio => $cruda) {
            $porFila[$cruda['fila']] = $cruda['valor'];
        }

        $resueltas = [];
        $resolver = function (int $fila) use (&$resolver, &$resueltas, $porFila): array {
            if (isset($resueltas[$fila])) {
                return $resueltas[$fila];
            }

            if (! array_key_exists($fila, $porFila)) {
                throw new \RuntimeException("La matriz del Calendario apunta a una fila sin servicio: {$fila}.");
            }

            $valor = $porFila[$fila];

            if (is_numeric($valor) && floor((float) $valor) === (float) $valor && (int) $valor >= 0) {
                return $resueltas[$fila] = ['modo' => CourierAcuerdoRegla::FIJO, 'dias_semana' => null, 'cantidad_fija' => (int) $valor];
            }

            $formula = strtoupper(str_replace([' ', '$'], '', (string) $valor));

            if (! preg_match('/^=\+?(?:(?:F[3-9]|I\d+)\+?)+$/', $formula)) {
                throw new \RuntimeException("No se reconoce la fórmula de días de la fila {$fila} del Calendario.");
            }

            preg_match_all('/([FI])(\d+)/', $formula, $partes, PREG_SET_ORDER);

            $semana = [];
            foreach ($partes as [, $columna, $numero]) {
                if ($columna === 'F') {
                    $semana[] = (int) $numero - 2;

                    continue;
                }

                $otra = $resolver((int) $numero);

                if ($otra['modo'] !== CourierAcuerdoRegla::DIAS_SEMANA) {
                    throw new \RuntimeException("La fórmula de la fila {$fila} del Calendario suma un servicio de días fijos.");
                }

                $semana = [...$semana, ...$otra['dias_semana']];
            }

            return $resueltas[$fila] = [
                'modo' => CourierAcuerdoRegla::DIAS_SEMANA,
                'dias_semana' => array_values(array_unique($semana)),
                'cantidad_fija' => null,
            ];
        };

        $reglas = [];
        foreach ($crudas as $servicio => $cruda) {
            $reglas[$servicio] = $resolver($cruda['fila']);
        }

        return $reglas;
    }

    private function fecha(mixed $valor): ?CarbonImmutable
    {
        if (! is_numeric($valor)) {
            return null;
        }

        return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $valor));
    }

    private function entero(mixed $valor, int $linea, string $columna): int
    {
        if ($valor === null || trim((string) $valor) === '') {
            return 0;
        }

        if (! is_numeric($valor) || (float) $valor < 0 || floor((float) $valor) !== (float) $valor) {
            throw new \RuntimeException("{$columna} no es un número entero en la fila {$linea}.");
        }

        return (int) $valor;
    }

    private function encabezado(mixed $valor): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $valor))) ?? '';
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }

    private function celda(int $columna, int $fila): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columna) . $fila;
    }
}
