<?php

namespace App\Services\Courier;

use App\Models\CourierBulto;
use App\Models\CourierCoberturaComuna;
use App\Models\CourierConfiguracion;
use App\Models\CourierControl;
use App\Models\CourierEstadoEntrega;
use App\Models\CourierPeriodo;
use App\Models\CourierProveedor;
use App\Models\CourierTarifa;
use Illuminate\Support\Facades\DB;

/*
 * Aplica a cada bulto del período la cadena de pago de la planilla.
 * El orden de los pasos está en storage/agents/courier/02-cadena-de-pago.md.
 *
 * No decide nada que no esté en los catálogos: lo que no tiene regla
 * queda marcado con su motivo, para que Operaciones lo resuelva.
 *
 * Se puede volver a ejecutar cuantas veces se quiera; reescribe las
 * columnas de cálculo del período y no toca lo que trajo Geolice.
 */
class CourierCalculoService
{
    public const TIPO_VARIABLES = 'Variables';
    public const TIPO_LANAS = 'Lanas';
    public const TIPO_RETORNOS = 'Retornos';

    /*
     * Un retorno es un bulto que vuelve desde regiones: Geolice lo
     * informa con el destinatario "Desde Concepción", "Desde-Local, Concepción"
     * o "Local, Concepción". Después de "Desde" puede venir espacio o guion:
     * así lo pagó LogisticaCL en agosto.
     */
    private const PATRON_RETORNO = '/^\s*desde[\s-]+/iu';

    /*
     * Comerciantes cuyo pago va a la hoja Geolize-Lanas en vez de
     * BaseGeolize-trabajada. Está verificado contra la réplica de
     * agosto: ninguno aparece en la otra hoja.
     */
    private const COMERCIANTES_LANAS = [
        'revesderecho',
        'comercial reginella ltda',
        '(orquidea) hilanderia maisa',
    ];

    /*
     * Lo que entrega el personal de 4N no se le paga a ningún proveedor.
     * En DatosProveedores esas filas tienen la razón social "Planta";
     * LogisticaCL las deja fuera de la misma forma (proveedor interno).
     */
    private const PROVEEDOR_INTERNO = 'planta';

    /*
     * Revés Derecho Mayorista (Paso a Paso del jefe, hoja de julio): lo
     * que va a regiones vuelve a RM con la comuna CD QUILICURA y se paga
     * ahí. Se quedan en su comuna los de Transporte Mandame y Curacaví.
     * LogisticaCL aplica la misma regla.
     */
    private const MAYORISTA_COMERCIANTE = 'revesderecho';
    private const MAYORISTA_SERVICIOS = ['servicio standar (mayorista)', 'standar (mayorista)'];
    private const MAYORISTA_COMUNA = 'CD QUILICURA';
    private const MAYORISTA_SE_QUEDAN = ['transporte mandame (talagante)', 'operador curacavi'];

    /* Motivos por los que un bulto no se paga. */
    public const MOTIVOS = [
        'pagado_mes_anterior' => 'Ya se pagó el mes anterior',
        'retorno' => 'Es un retorno',
        'blue' => 'Lo envió Blue Express',
        'especial' => 'Tiene pago especial autorizado',
        'sin_comuna' => 'Geolice no informó la comuna',
        'comuna_desconocida' => 'La comuna no está en el catálogo',
        'sin_configuracion' => 'No hay configuración de pago para esta combinación',
        'configuracion_no' => 'La configuración dice que no se paga',
        'configuracion_revisar' => 'La configuración está marcada para revisar',
        'tabla_cero' => 'La tabla de tarifa es 0 (no se paga)',
        'sin_tabla' => 'La configuración no tiene tabla asignada',
        'estado' => 'El estado de entrega descuenta el bulto',
        'sin_tarifa' => 'La tabla asignada no existe en el catálogo de tarifas',
        'retorno_sin_cobertura' => 'Retorno desde una comuna que no está en el catálogo',
        'retorno_no_se_paga' => 'La comuna de origen no paga retornos',
        'retorno_sin_valor' => 'La comuna paga retornos pero no tiene valor asignado',
        'proveedor_interno' => 'Lo entregó personal de 4N (Planta)',
    ];

    public function __construct(
        private readonly CourierCatalogoService $catalogo
    ) {
    }

    public function calcular(CourierPeriodo $periodo): array
    {
        set_time_limit(0);

        $resumen = [
            'bultos' => 0,
            'pagar' => 0,
            'descontar' => 0,
            'monto' => 0,
            'peso_bodega' => 0,
            'peso_declarado' => 0,
            'peso_por_defecto' => 0,
        ];

        $motivos = [];

        $cobertura = $this->cobertura();
        $configuraciones = $this->configuraciones();
        $estados = CourierEstadoEntrega::pluck('considerar', 'estado')->all();
        $tarifas = CourierTarifa::with(['tramos' => fn ($q) => $q->orderBy('peso')])->get()->keyBy('numero');
        $proveedores = $this->proveedores();
        $controles = $this->controles($periodo);
        $pesajes = $this->pesajes($periodo);

        DB::transaction(function () use (
            $periodo, &$resumen, &$motivos,
            $cobertura, $configuraciones, $estados, $tarifas, $proveedores, $controles, $pesajes
        ) {
            $ahora = now()->format('Y-m-d H:i:s');

            /*
             * Sólo las columnas que la cadena de pago necesita leer: con
             * los modelos completos, hidratar decenas de miles de filas
             * de cuarenta columnas cuesta más que el cálculo mismo.
             */
            DB::table('courier_bultos')
                ->where('courier_periodo_id', $periodo->id)
                ->select([
                    'id', 'seguimiento', 'comuna_destino', 'comerciante',
                    'servicio', 'estado_entrega', 'peso_declarado', 'repartidor_nombre',
                    'destinatario_nombre',
                ])
                ->orderBy('id')
                ->chunkById(2000, function ($bultos) use (
                    &$resumen, &$motivos, $ahora,
                    $cobertura, $configuraciones, $estados, $tarifas, $proveedores, $controles, $pesajes
                ) {
                    $calculos = [];

                    foreach ($bultos as $bulto) {
                        $calculo = $this->calcularBulto(
                            $bulto, $cobertura, $configuraciones, $estados,
                            $tarifas, $proveedores, $controles, $pesajes
                        );

                        $resumen['bultos']++;

                        if ($calculo['estado_pago'] === 'PAGAR') {
                            $resumen['pagar']++;
                            $resumen['monto'] += $calculo['valor'] ?? 0;
                        } else {
                            $resumen['descontar']++;
                            $motivo = $calculo['motivo'] ?? 'sin motivo';
                            $motivos[$motivo] = ($motivos[$motivo] ?? 0) + 1;
                        }

                        if ($calculo['origen_peso'] !== null) {
                            $resumen[match ($calculo['origen_peso']) {
                                'bodega' => 'peso_bodega',
                                'declarado' => 'peso_declarado',
                                default => 'peso_por_defecto',
                            }]++;
                        }

                        $calculos[$bulto->id] = $calculo + [
                            'calculado_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                    }

                    foreach (array_chunk($calculos, 300, true) as $lote) {
                        $this->guardarLote($lote);
                    }
                });
        });

        arsort($motivos);

        return ['resumen' => $resumen, 'motivos' => $motivos];
    }

    /*
     * Guarda un lote de cálculos con una sola sentencia.
     *
     * Actualizar bulto por bulto significa decenas de miles de viajes a
     * la base; con un CASE por columna son unas pocas sentencias y el
     * cálculo completo baja de minutos a segundos.
     *
     * @param  array<int,array<string,mixed>>  $calculos  id => columnas
     */
    private function guardarLote(array $calculos): void
    {
        if ($calculos === []) {
            return;
        }

        $ids = array_keys($calculos);
        $columnas = array_keys(reset($calculos));

        $asignaciones = [];
        $valores = [];

        foreach ($columnas as $columna) {
            $caso = "`{$columna}` = CASE `id`";

            foreach ($calculos as $id => $fila) {
                $caso .= ' WHEN ? THEN ?';
                $valores[] = $id;
                $valores[] = $fila[$columna];
            }

            $asignaciones[] = $caso . ' END';
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));

        DB::update(
            'UPDATE `courier_bultos` SET ' . implode(', ', $asignaciones) . " WHERE `id` IN ({$marcas})",
            array_merge($valores, $ids)
        );
    }

    /*
     * La cadena completa para un bulto. Devuelve exactamente las
     * columnas de cálculo de courier_bultos.
     *
     * $bulto trae sólo las columnas que hacen falta, leídas sin Eloquent.
     */
    private function calcularBulto(
        object $bulto,
        array $cobertura,
        array $configuraciones,
        array $estados,
        $tarifas,
        array $proveedores,
        array $controles,
        array $pesajes
    ): array {
        $salida = [
            'courier_agente_id' => null,
            'zona' => null,
            'tipo_pago' => $this->tipoPago($bulto->comerciante),
            'courier_configuracion_id' => null,
            'courier_proveedor_id' => null,
            'considerar_pago' => null,
            'tabla' => null,
            'peso_bodega' => null,
            'peso_pago' => null,
            'origen_peso' => null,
            'valor' => null,
            'estado_pago' => 'DESCONTAR',
            'motivo' => null,
        ];

        /* Paso 1 · comuna → agente y zona. */
        $clave = $bulto->comuna_destino === null || $bulto->comuna_destino === ''
            ? null
            : CourierCoberturaComuna::clave($bulto->comuna_destino);

        $comuna = $clave === null ? null : ($cobertura[$clave] ?? null);

        if ($this->esMayoristaQueVuelve($bulto, $comuna)) {
            $clave = CourierCoberturaComuna::clave(self::MAYORISTA_COMUNA);
            $comuna = $cobertura[$clave] ?? null;
        }

        if ($comuna !== null) {
            $salida['courier_agente_id'] = $comuna['agente_id'];
            $salida['zona'] = $comuna['zona'];
        }

        /* Paso 3 · kilos. Se resuelve siempre, aunque el bulto no se pague. */
        $pesoBodega = $pesajes[strtoupper($bulto->seguimiento)] ?? null;
        $salida['peso_bodega'] = $pesoBodega;

        if ($pesoBodega !== null && $pesoBodega > 0) {
            $salida['peso_pago'] = $pesoBodega;
            $salida['origen_peso'] = 'bodega';
        } elseif ($bulto->peso_declarado !== null && (float) $bulto->peso_declarado >= 1) {
            // Regla de la planilla: se trunca al entero, con mínimo 1.
            $salida['peso_pago'] = max(1, (int) floor((float) $bulto->peso_declarado));
            $salida['origen_peso'] = 'declarado';
        } else {
            $salida['peso_pago'] = 1;
            $salida['origen_peso'] = 'x';
        }

        /*
         * Paso 5 · controles que sacan el bulto antes que nada.
         *
         * Los retornos ya no se toman de la hoja Retornos de la planilla:
         * se reconocen por el destinatario y se pagan más abajo.
         */
        $codigo = strtoupper($bulto->seguimiento);

        foreach ([
            CourierControl::PAGADO_MES_ANTERIOR => 'pagado_mes_anterior',
            CourierControl::BLUE => 'blue',
            CourierControl::ESPECIAL => 'especial',
        ] as $tipo => $motivo) {
            if (isset($controles[$tipo][$codigo])) {
                $salida['motivo'] = $motivo;

                return $salida;
            }
        }

        /*
         * Lanas manda sobre retorno: un bulto de Revés Derecho que vuelve
         * desde regiones se sigue pagando como Lanas, igual que en
         * LogisticaCL, donde se pregunta por Lanas antes que por retorno.
         */
        if ($salida['tipo_pago'] !== self::TIPO_LANAS
            && preg_match(self::PATRON_RETORNO, (string) $bulto->destinatario_nombre) === 1) {
            return $this->calcularRetorno($bulto, $salida, $cobertura, $estados, $proveedores);
        }

        if ($clave === null) {
            $salida['motivo'] = 'sin_comuna';

            return $salida;
        }

        if ($comuna === null) {
            $salida['motivo'] = 'comuna_desconocida';

            return $salida;
        }

        /* Paso 2 · agente + comerciante + servicio → ¿se paga? ¿qué tabla? */
        $llave = CourierConfiguracion::llave($comuna['agente'], $bulto->comerciante, $bulto->servicio);
        $configuracion = $configuraciones[$llave] ?? null;

        if ($configuracion === null) {
            $salida['motivo'] = 'sin_configuracion';

            return $salida;
        }

        $salida['courier_configuracion_id'] = $configuracion['id'];
        $salida['considerar_pago'] = $configuracion['pagar'];
        $salida['tabla'] = $configuracion['tabla'];

        /* Paso 7 · a quién se le paga. */
        $proveedor = $proveedores[
            CourierProveedor::llave($comuna['agente'], (string) $bulto->repartidor_nombre)
        ] ?? null;

        $salida['courier_proveedor_id'] = $proveedor['id'] ?? null;

        if ($configuracion['pagar'] === 'NO') {
            $salida['motivo'] = 'configuracion_no';

            return $salida;
        }

        if ($configuracion['pagar'] === 'REVISAR') {
            $salida['motivo'] = 'configuracion_revisar';

            return $salida;
        }

        /* Estado de entrega. */
        if (($estados[$bulto->estado_entrega] ?? null) === CourierEstadoEntrega::DESCONTAR) {
            $salida['motivo'] = 'estado';

            return $salida;
        }

        if ($configuracion['tabla'] === null) {
            $salida['motivo'] = 'sin_tabla';

            return $salida;
        }

        if ((int) $configuracion['tabla'] === 0) {
            $salida['valor'] = 0;
            $salida['motivo'] = 'tabla_cero';

            return $salida;
        }

        $tarifa = $tarifas->get($configuracion['tabla']);

        if ($tarifa === null) {
            $salida['motivo'] = 'sin_tarifa';

            return $salida;
        }

        /*
         * Personal de 4N. Va al final para que este motivo cuente sólo
         * los bultos que, de no ser por él, se pagarían.
         */
        if ($proveedor !== null && $proveedor['interno']) {
            $salida['motivo'] = 'proveedor_interno';

            return $salida;
        }

        /* Paso 4 · tabla + kilos → valor. */
        $salida['valor'] = $this->catalogo->valorPorPeso($tarifa, $salida['peso_pago']) ?? 0;
        $salida['estado_pago'] = 'PAGAR';
        $salida['motivo'] = null;

        return $salida;
    }

    /*
     * Un retorno no se paga por kilo ni por configuración: se paga un
     * valor fijo que depende de la comuna desde donde vuelve el bulto,
     * y que está en el catálogo de cobertura (hoja Operador, columnas
     * PAGAR RETORNO y VALOR/RETORNO).
     *
     * La comuna de origen sale del nombre del destinatario:
     * "Desde Concepción" → Concepción; "Local 12, Concepción" → Concepción.
     */
    private function calcularRetorno(
        object $bulto,
        array $salida,
        array $cobertura,
        array $estados,
        array $proveedores
    ): array {
        $salida['tipo_pago'] = self::TIPO_RETORNOS;

        if (($estados[$bulto->estado_entrega] ?? null) === CourierEstadoEntrega::DESCONTAR) {
            $salida['motivo'] = 'estado';

            return $salida;
        }

        $nombre = trim((string) $bulto->destinatario_nombre);
        $origen = str_contains($nombre, ',')
            ? trim((string) substr($nombre, strpos($nombre, ',') + 1))
            : trim((string) preg_replace('/^\s*desde[\s-]*/iu', '', $nombre));

        $comuna = $origen === '' ? null : ($cobertura[CourierCoberturaComuna::clave($origen)] ?? null);

        if ($comuna === null) {
            $salida['motivo'] = 'retorno_sin_cobertura';

            return $salida;
        }

        $salida['courier_agente_id'] = $comuna['agente_id'];
        $salida['zona'] = $comuna['zona'];

        $proveedor = $proveedores[
            CourierProveedor::llave($comuna['agente'], (string) $bulto->repartidor_nombre)
        ] ?? null;

        $salida['courier_proveedor_id'] = $proveedor['id'] ?? null;

        if (! $comuna['pagar_retorno']) {
            $salida['motivo'] = 'retorno_no_se_paga';

            return $salida;
        }

        if ($comuna['valor_retorno'] === null || (int) $comuna['valor_retorno'] < 0) {
            $salida['motivo'] = 'retorno_sin_valor';

            return $salida;
        }

        if ($proveedor !== null && $proveedor['interno']) {
            $salida['motivo'] = 'proveedor_interno';

            return $salida;
        }

        $salida['valor'] = (int) $comuna['valor_retorno'];
        $salida['estado_pago'] = 'PAGAR';
        $salida['motivo'] = null;

        return $salida;
    }

    /*
     * Sin comuna o con una que no está en el catálogo también vuelve a
     * RM: sólo se quedan los agentes de MAYORISTA_SE_QUEDAN.
     */
    private function esMayoristaQueVuelve(object $bulto, ?array $comuna): bool
    {
        if (mb_strtolower(trim((string) $bulto->comerciante)) !== self::MAYORISTA_COMERCIANTE
            || ! in_array(mb_strtolower(trim((string) $bulto->servicio)), self::MAYORISTA_SERVICIOS, true)) {
            return false;
        }

        return ! in_array(mb_strtolower(trim($comuna['agente'] ?? '')), self::MAYORISTA_SE_QUEDAN, true);
    }

    private function tipoPago(?string $comerciante): string
    {
        return in_array(mb_strtolower(trim((string) $comerciante)), self::COMERCIANTES_LANAS, true)
            ? self::TIPO_LANAS
            : self::TIPO_VARIABLES;
    }

    private function cobertura(): array
    {
        return CourierCoberturaComuna::query()
            ->with('agente:id,nombre')
            ->get(['localidad_clave', 'zona', 'courier_agente_id', 'pagar_retorno', 'valor_retorno'])
            ->mapWithKeys(fn ($c) => [
                $c->localidad_clave => [
                    'agente_id' => $c->courier_agente_id,
                    'agente' => $c->agente?->nombre ?? '',
                    'zona' => $c->zona,
                    'pagar_retorno' => (bool) $c->pagar_retorno,
                    'valor_retorno' => $c->valor_retorno,
                ],
            ])
            ->all();
    }

    /* llave → id del proveedor y si es personal de 4N. */
    private function proveedores(): array
    {
        return CourierProveedor::query()
            ->get(['id', 'llave', 'razon_social'])
            ->mapWithKeys(fn ($p) => [
                $p->llave => [
                    'id' => $p->id,
                    'interno' => mb_strtolower(trim((string) $p->razon_social)) === self::PROVEEDOR_INTERNO,
                ],
            ])
            ->all();
    }

    private function configuraciones(): array
    {
        return CourierConfiguracion::query()
            ->get(['id', 'llave', 'pagar', 'tabla'])
            ->mapWithKeys(fn ($c) => [
                $c->llave => ['id' => $c->id, 'pagar' => $c->pagar, 'tabla' => $c->tabla],
            ])
            ->all();
    }

    /* tipo → [seguimiento => true] */
    private function controles(CourierPeriodo $periodo): array
    {
        $controles = [];

        CourierControl::query()
            ->delPeriodo($periodo->id)
            ->select(['tipo', 'seguimiento'])
            ->chunk(20000, function ($filas) use (&$controles) {
                foreach ($filas as $fila) {
                    $controles[$fila->tipo][strtoupper($fila->seguimiento)] = true;
                }
            });

        return $controles;
    }

    /*
     * seguimiento → kilos de balanza. Cuando un bulto se pesó en varios
     * días se conserva el primero, igual que el BUSCARV de la planilla.
     * (Pendiente de confirmar con Operaciones cuál debería mandar.)
     */
    private function pesajes(CourierPeriodo $periodo): array
    {
        $pesajes = [];

        DB::table('courier_pesajes')
            ->where('courier_periodo_id', $periodo->id)
            ->select(['seguimiento', 'kilos'])
            ->orderBy('id')
            ->chunk(50000, function ($filas) use (&$pesajes) {
                foreach ($filas as $fila) {
                    $pesajes[strtoupper($fila->seguimiento)] ??= (int) $fila->kilos;
                }
            });

        return $pesajes;
    }
}
