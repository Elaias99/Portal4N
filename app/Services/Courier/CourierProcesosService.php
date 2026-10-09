<?php

namespace App\Services\Courier;

use App\Models\CourierAcuerdo;
use App\Models\CourierApoyoAlzaRegla;
use App\Models\CourierPagoProceso;
use App\Models\CourierPeriodo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/*
 * Ruta CV, Servicios, Visitas, Especiales y Apoyo Alza, con las mismas
 * plantillas y reglas que LogisticaCL. Cada archivo se lee desde su
 * primera hoja:
 *
 *  - Ruta CV:    Base Ruta CV. Días 1 a 31 marcados con x; total =
 *                (días marcados − inasistencia) × valor, o el monto fijo
 *                si la columna Total trae un número en vez de fórmula.
 *  - Servicios:  Base_Servicios. Cada fila trae su valor final.
 *  - Visitas:    Base_Visitas. total = días × valor por día; los días
 *                salen de la columna Días o, si no está, de la frecuencia.
 *  - Especiales: base de pagos especiales. Cada fila trae su monto.
 *  - Apoyo Alza: un porcentaje sobre lo que el proveedor gana en Acuerdos
 *                o Variables, o un monto por día de Ruta CV. Se carga al
 *                final, cuando lo demás ya está.
 *
 * Al final de cada plantilla se aceptan columnas opcionales «RUT
 * Proveedor», «Días» y «Zona» cuando la plantilla no las trae.
 */
class CourierProcesosService
{
    private const ZONAS = ['RM', 'Regiones'];

    public function __construct(
        private readonly CourierProveedoresPorRut $proveedores
    ) {
    }

    /*
     * Reemplaza los pagos del proceso en el período con los del archivo.
     * Ante el primer dato que no se puede leer lanza una excepción con la
     * fila y el motivo, sin guardar nada.
     */
    public function importar(string $proceso, string $ruta, string $nombreArchivo, CourierPeriodo $periodo): array
    {
        $libro = IOFactory::load($ruta);

        try {
            $hoja = $libro->getSheet(0);

            $filas = match ($proceso) {
                CourierPagoProceso::RUTA_CV => $this->rutasCv($hoja, $periodo),
                CourierPagoProceso::SERVICIOS => $this->servicios($hoja),
                CourierPagoProceso::VISITAS => $this->visitas($hoja, $periodo),
                CourierPagoProceso::ESPECIALES => $this->especiales($hoja),
                CourierPagoProceso::APOYO_ALZA => $this->apoyoAlza($hoja),
            };
        } finally {
            $libro->disconnectWorksheets();
        }

        if ($filas === []) {
            throw new \RuntimeException('El archivo no trae filas para cargar.');
        }

        return DB::transaction(function () use ($proceso, $nombreArchivo, $periodo, $filas) {
            CourierPagoProceso::delPeriodo($periodo->id)->where('proceso', $proceso)->delete();

            foreach ($filas as $fila) {
                CourierPagoProceso::create($fila + [
                    'courier_periodo_id' => $periodo->id,
                    'proceso' => $proceso,
                    'archivo_origen' => $nombreArchivo,
                ]);
            }

            $estados = $proceso === CourierPagoProceso::APOYO_ALZA ? $this->calcularApoyo($periodo) : [];

            /* Los días de Ruta CV son base de Apoyo Alza. */
            if ($proceso === CourierPagoProceso::RUTA_CV) {
                $this->recalcularApoyo($periodo);
            }

            $cargados = CourierPagoProceso::delPeriodo($periodo->id)->where('proceso', $proceso);

            return [
                'filas' => (clone $cargados)->count(),
                'total' => (int) (clone $cargados)->sum('total'),
                'sin_proveedor' => (clone $cargados)->whereNull('courier_proveedor_id')->count(),
                'sin_zona' => (clone $cargados)->whereNull('zona')->count(),
                'estados' => $estados,
            ];
        });
    }

    /*
     * Reemplaza la lista completa de reglas de Apoyo Alza con la de la
     * plantilla (primera hoja, mismas columnas que LogisticaCL). No toca
     * ningún período: las reglas se aplican al calcular.
     */
    public function cargarReglasApoyo(string $ruta, string $nombreArchivo): int
    {
        $libro = IOFactory::load($ruta);

        try {
            $filas = $this->apoyoAlza($libro->getSheet(0));
        } finally {
            $libro->disconnectWorksheets();
        }

        if ($filas === []) {
            throw new \RuntimeException('La plantilla no trae reglas.');
        }

        return DB::transaction(function () use ($filas, $nombreArchivo) {
            CourierApoyoAlzaRegla::query()->delete();

            foreach ($filas as $fila) {
                $d = $fila['datos'];

                CourierApoyoAlzaRegla::create([
                    'fila_origen' => $fila['fila_origen'],
                    'proveedor' => $fila['proveedor'],
                    'rut_proveedor' => $fila['rut_proveedor'],
                    'zona' => $fila['zona'],
                    'proceso_base' => $d['proceso_base'],
                    'servicio_acuerdo' => $d['servicio_acuerdo'],
                    'factor' => $d['factor'],
                    'porcentaje' => $d['porcentaje'],
                    'monto_dia' => $d['monto_dia'],
                    'empresa_mandante' => $d['empresa_mandante'],
                    'agencia' => $d['agencia'],
                    'archivo_origen' => $nombreArchivo,
                ]);
            }

            return count($filas);
        });
    }

    /*
     * Vuelve a calcular el Apoyo Alza que el período ya tiene cargado, con
     * sus propias filas: cada mes trae su archivo, igual que en LogisticaCL.
     * Si el período no tiene Apoyo Alza, o está cerrado, no hace nada y
     * devuelve null.
     *
     * Se llama al calcular los bultos, al cargar Acuerdos y al cargar
     * Ruta CV, porque esas son sus bases.
     *
     * @return array{filas: int, total: int, estados: array<string, int>}|null
     */
    public function recalcularApoyo(CourierPeriodo $periodo): ?array
    {
        $filas = CourierPagoProceso::delPeriodo($periodo->id)->where('proceso', CourierPagoProceso::APOYO_ALZA);

        if ($periodo->estaCerrado() || ! (clone $filas)->exists()) {
            return null;
        }

        $estados = $this->calcularApoyo($periodo);

        return [
            'filas' => (clone $filas)->count(),
            'total' => (int) (clone $filas)->sum('total'),
            'estados' => $estados,
        ];
    }

    /*
     * Apoyo Alza, igual que LogisticaCL: la base es lo que el proveedor
     * gana en el período en Acuerdos (sólo los servicios que nombra la
     * fila, separados por «|»), en Variables, o los días trabajados de
     * Ruta CV. Si la base todavía no existe, la fila queda en 0 con su
     * motivo. Hay que repetirlo si después cambian Acuerdos, Ruta CV o el
     * cálculo de los bultos.
     *
     * @return array<string, int> estado → filas
     */
    public function calcularApoyo(CourierPeriodo $periodo): array
    {
        $bases = [];
        $sumar = function (string $clave, int $monto, int $dias) use (&$bases) {
            $bases[$clave] ??= ['registros' => 0, 'monto' => 0, 'dias' => 0];
            $bases[$clave]['registros']++;
            $bases[$clave]['monto'] += $monto;
            $bases[$clave]['dias'] += $dias;
        };

        foreach (CourierAcuerdo::delPeriodo($periodo->id)->get(['rut_proveedor', 'servicio', 'total', 'cantidad']) as $a) {
            $sumar($this->claveBase('Acuerdos', $a->rut_proveedor, $a->servicio), $a->total, $a->cantidad);
        }

        foreach (CourierPagoProceso::delPeriodo($periodo->id)->where('proceso', CourierPagoProceso::RUTA_CV)->get(['rut_proveedor', 'total', 'cantidad']) as $r) {
            $sumar($this->claveBase('Ruta CV', $r->rut_proveedor, ''), $r->total, (int) $r->cantidad);
        }

        DB::table('courier_bultos')
            ->join('courier_proveedores', 'courier_proveedores.id', '=', 'courier_bultos.courier_proveedor_id')
            ->where('courier_bultos.courier_periodo_id', $periodo->id)
            ->where('courier_bultos.estado_pago', 'PAGAR')
            ->where('courier_bultos.tipo_pago', CourierCalculoService::TIPO_VARIABLES)
            ->select(['courier_proveedores.rut', 'courier_bultos.valor'])
            ->orderBy('courier_bultos.id')
            ->chunk(5000, function ($filas) use ($sumar) {
                foreach ($filas as $fila) {
                    $sumar($this->claveBase('Variables', $fila->rut, ''), (int) $fila->valor, 0);
                }
            });

        $estados = [];

        foreach (CourierPagoProceso::delPeriodo($periodo->id)->where('proceso', CourierPagoProceso::APOYO_ALZA)->get() as $fila) {
            $d = $fila->datos;
            $base = $this->baseApoyo($d['proceso_base'], $fila->rut_proveedor, (string) ($d['servicio_acuerdo'] ?? ''), $bases);

            $estado = 'calculado';
            $monto = 0;

            if (($d['factor'] === 'Dia de Ruta CV' && ($d['monto_dia'] ?? null) === 0)
                || ($d['factor'] === '%' && ($d['porcentaje'] ?? null) !== null && (float) $d['porcentaje'] === 0.0)) {
                $estado = 'no_pagar';
            } elseif ($fila->courier_proveedor_id === null) {
                $estado = 'sin_proveedor';
            } elseif ($base === null) {
                $estado = 'sin_base';
            } elseif ($d['proceso_base'] === 'Ruta CV') {
                $monto = $base['dias'] * (int) $d['monto_dia'];
            } else {
                $monto = (int) round($base['monto'] * (float) $d['porcentaje'], 0, PHP_ROUND_HALF_UP);
            }

            if ($estado === 'calculado' && $monto === 0) {
                $estado = 'no_pagar';
            }

            $fila->update([
                'valor_unitario' => $d['proceso_base'] === 'Ruta CV' ? $d['monto_dia'] : null,
                'cantidad' => $d['proceso_base'] === 'Ruta CV' ? ($base['dias'] ?? null) : null,
                'total' => $monto,
                'datos' => array_merge($d, [
                    'estado_calculo' => $estado,
                    'registros_base' => $base['registros'] ?? 0,
                    'monto_base' => $base['monto'] ?? null,
                ]),
            ]);

            $estados[$estado] = ($estados[$estado] ?? 0) + 1;
        }

        return $estados;
    }

    /* ---------- Plantillas ---------- */

    private function rutasCv(Worksheet $hoja, CourierPeriodo $periodo): array
    {
        $this->exigirEncabezados($hoja, [
            'Periodo', 'Proceso', 'Zona', 'Servicio', 'Frecuencia', 'Facturador', 'Usuario',
            'Detalle-Ruta', 'Comuna', 'Producto', 'Valor', 'Agente',
        ], 'Base Ruta CV');

        for ($dia = 1; $dia <= 31; $dia++) {
            if ((int) $this->valor($hoja, $dia + 12, 1) !== $dia) {
                throw new \RuntimeException('Faltan las columnas de días 1 a 31 en Base Ruta CV.');
            }
        }

        $traeRut = $this->encabezado($this->valor($hoja, 48, 1)) === 'rutproveedor';
        $filas = [];

        for ($linea = 2; $linea <= $hoja->getHighestDataRow(); $linea++) {
            $facturador = $this->texto($this->valor($hoja, 6, $linea));

            if ($facturador === null) {
                continue;
            }

            if (trim((string) $this->valor($hoja, 1, $linea)) !== $periodo->codigo) {
                throw new \RuntimeException("La fila {$linea} es de otro período que {$periodo->codigo}.");
            }

            $valor = $this->entero($this->valor($hoja, 11, $linea), $linea, 'Valor');

            $dias = [];
            for ($dia = 1; $dia <= 31; $dia++) {
                if (mb_strtoupper(trim((string) $this->valor($hoja, $dia + 12, $linea))) === 'X') {
                    $dias[] = $dia;
                }
            }

            /* Total con número = monto fijo; con fórmula = se paga por día. */
            $totalCrudo = $hoja->getCell('AT' . $linea)->getValue();
            $fijo = is_numeric($totalCrudo) && ! str_starts_with((string) $totalCrudo, '=');

            $inasistencia = $this->entero($this->valor($hoja, 45, $linea), $linea, 'Inasistencia');
            if ($inasistencia > count($dias)) {
                throw new \RuntimeException("La inasistencia supera los días marcados en la fila {$linea}.");
            }

            $trabajados = count($dias) - $inasistencia;

            $filas[] = $this->conProveedor([
                'fila_origen' => $linea,
                'proveedor' => $facturador,
                'rut_proveedor' => $traeRut ? $this->texto($this->valor($hoja, 48, $linea)) : null,
                'concepto' => $this->texto($this->valor($hoja, 8, $linea)),
                'valor_unitario' => $valor,
                'cantidad' => $trabajados,
                'total' => $fijo ? (int) $totalCrudo : max(0, $trabajados) * $valor,
                'dias' => $dias,
                'datos' => [
                    'servicio' => $this->texto($this->valor($hoja, 4, $linea)),
                    'frecuencia' => $this->texto($this->valor($hoja, 5, $linea)),
                    'usuario' => $this->texto($this->valor($hoja, 7, $linea)),
                    'comuna' => $this->texto($this->valor($hoja, 9, $linea)),
                    'producto' => $this->texto($this->valor($hoja, 10, $linea)),
                    'agente' => $this->texto($this->valor($hoja, 12, $linea)),
                    'inasistencia' => $inasistencia,
                    'tipo_cobro' => $fijo ? 'fijo' : 'diario',
                    'observacion' => $this->texto($this->valor($hoja, 47, $linea)),
                ],
            ], $this->valor($hoja, 3, $linea));
        }

        return $filas;
    }

    private function servicios(Worksheet $hoja): array
    {
        $esperados = [
            'zona', 'tipodepago', 'seguimientopaquete', 'fechacarga', 'direccion', 'numerodestino',
            'deptodestino', 'comunadestino', 'razonsocialcliente', 'rutcliente', 'servicio', 'peso',
            'estadodelenvio', 'valorfinal', 'operador', 'usuario', 'periodo', 'usuario2',
            'transportista', 'razonsocial', 'rut', 'empresa',
        ];

        $encontrados = [];
        for ($columna = 1; $columna <= 22; $columna++) {
            $encontrados[] = $this->encabezado($this->valor($hoja, $columna, 1));
        }
        if (($encontrados[20] ?? null) === 'rutproveedor') {
            $encontrados[20] = 'rut';
        }
        if ($encontrados !== $esperados) {
            throw new \RuntimeException('Las columnas no son las de la plantilla Base_Servicios.');
        }

        $filas = [];

        for ($linea = 2; $linea <= $hoja->getHighestDataRow(); $linea++) {
            $v = fn (int $columna) => $this->valor($hoja, $columna, $linea);

            if ($this->filaVacia($hoja, $linea, 22)) {
                continue;
            }

            if ($this->encabezado($v(2)) !== 'servicios') {
                throw new \RuntimeException("La fila {$linea} no es del proceso Servicios.");
            }

            $filas[] = $this->conProveedor([
                'fila_origen' => $linea,
                'proveedor' => $this->texto($v(20)),
                'rut_proveedor' => $this->texto($v(21)),
                'concepto' => $this->texto($v(11)),
                'total' => $this->entero($v(14), $linea, 'Valor final'),
                'datos' => [
                    'seguimiento' => $this->texto($v(3)),
                    'fecha' => $this->fecha($v(4)),
                    'direccion' => $this->texto($v(5)),
                    'comuna' => $this->texto($v(8)),
                    'cliente' => $this->texto($v(9)),
                    'rut_cliente' => $this->texto($v(10)),
                    'peso' => $v(12),
                    'estado' => $this->texto($v(13)),
                    'operador' => $this->texto($v(15)),
                    'usuario' => $this->texto($v(16)),
                    'periodo' => $this->texto($v(17)),
                    'transportista' => $this->texto($v(19)),
                    'empresa' => $this->texto($v(22)),
                ],
            ], $v(1));
        }

        return $filas;
    }

    private function visitas(Worksheet $hoja, CourierPeriodo $periodo): array
    {
        $this->exigirEncabezados($hoja, [
            'NuevoAgente', 'Local', 'Nombre del Local', 'Direccion', 'Comuna', 'Frecuencia',
            'SLA Operador desde RM (Paq)', 'SLA Cliente desde RM (Paq)', 'SLA desde Locales a CD',
            'Estatus', 'Razón social proveedor', 'Nombre de pila proveedor', 'RUT proveedor',
            'Valor x Dia', 'Cliente', 'Rut Cliente', 'Comerciante (Pila)',
        ], 'Base_Visitas');

        $traeDias = $this->encabezado($this->valor($hoja, 18, 1)) === 'dias';
        $traeZona = $this->encabezado($this->valor($hoja, 19, 1)) === 'zona';
        $mes = CarbonImmutable::create($periodo->anio, $periodo->mes, 1);
        $filas = [];

        for ($linea = 2; $linea <= $hoja->getHighestDataRow(); $linea++) {
            $v = fn (int $columna) => $this->valor($hoja, $columna, $linea);

            if ($this->texto($v(2)) === null && $this->texto($v(3)) === null) {
                continue;
            }

            $valorDia = $this->entero($v(14), $linea, 'Valor x Dia');
            $dias = $traeDias
                ? $this->listaDias((string) $v(18), $mes, $linea)
                : $this->diasDeFrecuencia((string) $v(6), $mes);

            $filas[] = $this->conProveedor([
                'fila_origen' => $linea,
                'proveedor' => $this->texto($v(11)),
                'rut_proveedor' => $this->texto($v(13)),
                'concepto' => $this->texto($v(3)),
                'valor_unitario' => $valorDia,
                'cantidad' => count($dias),
                'total' => count($dias) * $valorDia,
                'dias' => $dias,
                'datos' => [
                    'agente' => $this->texto($v(1)),
                    'local' => $this->texto($v(2)),
                    'direccion' => $this->texto($v(4)),
                    'comuna' => $this->texto($v(5)),
                    'frecuencia' => $this->texto($v(6)),
                    'estatus' => $this->texto($v(10)),
                    'cliente' => $this->texto($v(15)),
                    'rut_cliente' => $this->texto($v(16)),
                    'comerciante' => $this->texto($v(17)),
                ],
            ], $traeZona ? $v(19) : null);
        }

        return $filas;
    }

    private function especiales(Worksheet $hoja): array
    {
        $this->exigirEncabezados($hoja, [
            'Fecha', 'Usuario Ingresa', 'Autoriza', 'Agente', 'Zona / Tipo', 'ID',
            'Localidad', 'Cliente', 'Descripción', 'Monto',
        ], 'pagos especiales');

        $traeRut = $this->encabezado($this->valor($hoja, 11, 1)) === 'rutproveedor';
        $traeZona = $this->encabezado($this->valor($hoja, 12, 1)) === 'zona';
        $filas = [];

        for ($linea = 2; $linea <= $hoja->getHighestDataRow(); $linea++) {
            $v = fn (int $columna) => $this->valor($hoja, $columna, $linea);

            if ($this->filaVacia($hoja, $linea, 10)) {
                continue;
            }

            $filas[] = $this->conProveedor([
                'fila_origen' => $linea,
                'proveedor' => $this->texto($v(4)),
                'rut_proveedor' => $traeRut ? $this->texto($v(11)) : null,
                'concepto' => $this->texto($v(9)),
                'total' => $this->entero($v(10), $linea, 'Monto'),
                'datos' => [
                    'fecha' => $this->fecha($v(1)),
                    'usuario_ingresa' => $this->texto($v(2)),
                    'autoriza' => $this->texto($v(3)),
                    'zona_tipo' => $this->texto($v(5)),
                    'seguimiento' => $this->texto($v(6)),
                    'localidad' => $this->texto($v(7)),
                    'cliente' => $this->texto($v(8)),
                ],
            ], $traeZona ? $v(12) : null);
        }

        return $filas;
    }

    private function apoyoAlza(Worksheet $hoja): array
    {
        $this->exigirEncabezados($hoja, [
            'Razón Social Cliente', 'RUT Cliente', 'Proceso', 'Servicio de Acuerdo', 'Factor',
            'Porcentaje', 'Monto', 'Empresa Mandante', 'Agencia',
        ], 'Apoyo Alza');

        $traeZona = $this->encabezado($this->valor($hoja, 10, 1)) === 'zona';
        $filas = [];

        for ($linea = 2; $linea <= $hoja->getHighestDataRow(); $linea++) {
            $v = fn (int $columna) => $this->valor($hoja, $columna, $linea);

            if ($this->filaVacia($hoja, $linea, 9)) {
                continue;
            }

            $proceso = trim((string) $v(3));
            $factor = trim((string) $v(5));
            $servicio = $this->texto($v(4));

            if (! in_array($proceso, ['Acuerdos', 'Variables', 'Ruta CV'], true)) {
                throw new \RuntimeException("El proceso de la fila {$linea} debe ser Acuerdos, Variables o Ruta CV.");
            }

            if (($proceso === 'Ruta CV') !== ($factor === 'Dia de Ruta CV') || ($proceso !== 'Ruta CV' && $factor !== '%')) {
                throw new \RuntimeException("El factor de la fila {$linea} no corresponde al proceso {$proceso}.");
            }

            if ($proceso === 'Acuerdos' && $servicio === null) {
                throw new \RuntimeException("Falta el servicio de Acuerdos en la fila {$linea}.");
            }

            $filas[] = $this->conProveedor([
                'fila_origen' => $linea,
                'proveedor' => $this->texto($v(1)),
                'rut_proveedor' => $this->texto($v(2)),
                'concepto' => $proceso . ($servicio !== null ? ' · ' . $servicio : ''),
                'total' => 0,
                'datos' => [
                    'proceso_base' => $proceso,
                    'servicio_acuerdo' => $servicio,
                    'factor' => $factor,
                    'porcentaje' => $factor === '%' ? $this->porcentaje($v(6), $linea) : null,
                    'monto_dia' => $factor === 'Dia de Ruta CV' ? $this->entero($v(7), $linea, 'Monto') : null,
                    'empresa_mandante' => $this->texto($v(8)),
                    'agencia' => $this->texto($v(9)),
                ],
            ], $traeZona ? $v(10) : null);
        }

        return $filas;
    }

    /* ---------- Apoyo Alza ---------- */

    private function claveBase(string $proceso, ?string $rut, string $servicio): string
    {
        $servicio = $proceso === 'Acuerdos' ? Str::of($servicio)->squish()->ascii()->lower()->toString() : '';

        return $proceso . '|' . CourierProveedoresPorRut::normalizar($rut) . '|' . $servicio;
    }

    /** @return array{registros: int, monto: int, dias: int}|null */
    private function baseApoyo(string $proceso, ?string $rut, string $servicios, array $bases): ?array
    {
        if ($proceso !== 'Acuerdos') {
            return $bases[$this->claveBase($proceso, $rut, '')] ?? null;
        }

        $claves = collect(explode('|', $servicios))
            ->map(fn (string $s) => trim($s))
            ->filter()
            ->map(fn (string $s) => $this->claveBase($proceso, $rut, $s))
            ->unique();

        if ($claves->isEmpty()) {
            return null;
        }

        $suma = ['registros' => 0, 'monto' => 0, 'dias' => 0];

        foreach ($claves as $clave) {
            if (! isset($bases[$clave])) {
                return null;
            }

            foreach ($suma as $campo => $valor) {
                $suma[$campo] += $bases[$clave][$campo];
            }
        }

        return $suma;
    }

    /* ---------- Ayudas ---------- */

    /* Proveedor por RUT y zona: la del archivo si es RM o Regiones, si no la del catálogo. */
    private function conProveedor(array $fila, mixed $zonaArchivo): array
    {
        $proveedor = $this->proveedores->buscar($fila['rut_proveedor'] ?? null);
        $zona = trim((string) $zonaArchivo);

        return $fila + [
            'courier_proveedor_id' => $proveedor['id'] ?? null,
            'zona' => in_array($zona, self::ZONAS, true) ? $zona : ($proveedor['zona'] ?? null),
        ];
    }

    /* Días de visita según la frecuencia, igual que LogisticaCL. */
    private function diasDeFrecuencia(string $frecuencia, CarbonImmutable $mes): array
    {
        $frecuencia = $this->normalizar($frecuencia);

        if (str_contains($frecuencia, '15 dias') || str_contains($frecuencia, 'quincenal')) {
            return [15, $mes->daysInMonth];
        }

        if (str_contains($frecuencia, '30 dias') || str_contains($frecuencia, 'mensual')) {
            return [$mes->daysInMonth];
        }

        $semana = match (true) {
            str_contains($frecuencia, 'lunes a viernes') => [1, 2, 3, 4, 5],
            str_contains($frecuencia, 'lunes miercoles viernes') => [1, 3, 5],
            str_contains($frecuencia, 'martes y jueves') => [2, 4],
            $frecuencia === 'miercoles' => [3],
            $frecuencia === 'jueves' => [4],
            default => [],
        };

        $dias = [];
        for ($dia = 1; $dia <= $mes->daysInMonth; $dia++) {
            if (in_array($mes->day($dia)->dayOfWeekIso, $semana, true)) {
                $dias[] = $dia;
            }
        }

        return $dias;
    }

    private function listaDias(string $texto, CarbonImmutable $mes, int $linea): array
    {
        $dias = [];

        foreach (array_filter(array_map('trim', explode(',', $texto)), fn ($d) => $d !== '') as $dia) {
            if (! ctype_digit($dia) || (int) $dia < 1 || (int) $dia > $mes->daysInMonth) {
                throw new \RuntimeException("Día inválido «{$dia}» en la fila {$linea}.");
            }

            $dias[] = (int) $dia;
        }

        return array_values(array_unique($dias));
    }

    private function exigirEncabezados(Worksheet $hoja, array $esperados, string $plantilla): void
    {
        foreach ($esperados as $i => $esperado) {
            if ($this->encabezado($this->valor($hoja, $i + 1, 1)) !== $this->encabezado($esperado)) {
                throw new \RuntimeException("Las columnas no son las de la plantilla {$plantilla}.");
            }
        }
    }

    private function filaVacia(Worksheet $hoja, int $linea, int $columnas): bool
    {
        for ($columna = 1; $columna <= $columnas; $columna++) {
            if (trim((string) $this->valor($hoja, $columna, $linea)) !== '') {
                return false;
            }
        }

        return true;
    }

    private function valor(Worksheet $hoja, int $columna, int $fila): mixed
    {
        return $hoja->getCell(Coordinate::stringFromColumnIndex($columna) . $fila)->getCalculatedValue();
    }

    private function porcentaje(mixed $valor, int $linea): string
    {
        $texto = trim((string) $valor);
        $conSigno = str_ends_with($texto, '%');
        $texto = rtrim($texto, '%');

        if (! is_numeric($texto)) {
            throw new \RuntimeException("El porcentaje de la fila {$linea} no es un número.");
        }

        $tasa = (float) $texto / ($conSigno ? 100 : 1);

        if ($tasa < 0 || $tasa > 1) {
            throw new \RuntimeException("El porcentaje de la fila {$linea} debe estar entre 0 % y 100 %.");
        }

        return number_format($tasa, 6, '.', '');
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

    private function fecha(mixed $valor): ?string
    {
        return is_numeric($valor)
            ? CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $valor))->toDateString()
            : $this->texto($valor);
    }

    private function encabezado(mixed $valor): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $valor))) ?? '';
    }

    private function normalizar(string $valor): string
    {
        $valor = Str::ascii(mb_strtolower(trim($valor)));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $valor) ?? '');
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }
}
