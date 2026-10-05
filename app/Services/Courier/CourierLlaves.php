<?php

namespace App\Services\Courier;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * La llave de pago de Operaciones, igual que en LogisticaCL:
 *
 *   1. El agente de la comuna dice el RUT del proveedor. Si el bulto lo
 *      entregó personal de 4N que en realidad trabaja para otro
 *      proveedor, se usa el RUT de ese proveedor.
 *   2. RUT proveedor + RUT cliente + código de servicio → SI / NO /
 *      REVISAR y la tabla de tarifa.
 *
 * Sólo resuelve; no lee bultos ni guarda nada. Los datos salen de las
 * tablas courier_llave_* (desdeBase) o directo del Excel (desdeFilas).
 */
class CourierLlaves
{
    /* Hay más de una llave posible y no dicen lo mismo. */
    public const AMBIGUA = 'ambigua';

    /**
     * @param  array<string, string>  $agentes  agente_clave => RUT
     * @param  array<string, string>  $clientes  comerciante_clave => RUT
     * @param  array<string, int>  $servicios  servicio_clave => código
     * @param  array<string, list<array{agente_clave: ?string, pagar: string, tabla: ?int}>>  $llaves  RUT|RUT|código => llaves
     * @param  array<string, string>  $repartidores  RUT|agente_clave|repartidor_clave => nuevo RUT
     */
    public function __construct(
        private readonly array $agentes,
        private readonly array $clientes,
        private readonly array $servicios,
        private readonly array $llaves,
        private readonly array $repartidores
    ) {
    }

    public static function desdeBase(): self
    {
        return self::desdeFilas([
            'agentes' => DB::table('courier_llave_agentes')->get(['agente', 'rut_proveedor'])->map(fn ($f) => (array) $f)->all(),
            'clientes' => DB::table('courier_llave_clientes')->get(['comerciante', 'rut_cliente'])->map(fn ($f) => (array) $f)->all(),
            'servicios' => DB::table('courier_llave_servicios')->get(['servicio', 'codigo'])->map(fn ($f) => (array) $f)->all(),
            'llaves' => DB::table('courier_llaves')->get(['rut_proveedor', 'agente', 'rut_cliente', 'codigo_servicio', 'pagar', 'tabla'])->map(fn ($f) => (array) $f)->all(),
            'repartidores' => DB::table('courier_llave_repartidores')->get(['rut_proveedor', 'agente', 'repartidor', 'nuevo_rut_proveedor'])->map(fn ($f) => (array) $f)->all(),
        ]);
    }

    /** @param  array<string, list<array<string, mixed>>>  $filas  como las deja CourierLlavesService::leer() */
    public static function desdeFilas(array $filas): self
    {
        $agentes = [];
        foreach ($filas['agentes'] as $fila) {
            $agentes[self::clave($fila['agente'])] = self::rut($fila['rut_proveedor']);
        }

        $clientes = [];
        foreach ($filas['clientes'] as $fila) {
            $clientes[self::claveTexto($fila['comerciante'])] = self::rut($fila['rut_cliente']);
        }

        $servicios = [];
        foreach ($filas['servicios'] as $fila) {
            $servicios[self::claveTexto($fila['servicio'])] = (int) $fila['codigo'];
        }

        $llaves = [];
        foreach ($filas['llaves'] as $fila) {
            $llaves[self::rut($fila['rut_proveedor']) . '|' . self::rut($fila['rut_cliente']) . '|' . (int) $fila['codigo_servicio']][] = [
                'agente_clave' => $fila['agente'] === null || trim((string) $fila['agente']) === '' ? null : self::clave($fila['agente']),
                'pagar' => strtoupper(trim((string) $fila['pagar'])),
                'tabla' => $fila['tabla'] === null || $fila['tabla'] === '' ? null : (int) $fila['tabla'],
            ];
        }

        $repartidores = [];
        foreach ($filas['repartidores'] as $fila) {
            $repartidores[self::claveRepartidor($fila['rut_proveedor'], $fila['agente'], $fila['repartidor'])]
                = self::rut($fila['nuevo_rut_proveedor']);
        }

        return new self($agentes, $clientes, $servicios, $llaves, $repartidores);
    }

    /* RUT del proveedor al que se le paga lo que entregó $repartidor en $agente. */
    public function rutProveedor(string $agente, ?string $repartidor): ?string
    {
        $rut = $this->agentes[self::clave($agente)] ?? null;

        if ($rut === null) {
            return null;
        }

        $nuevo = $this->repartidores[self::claveRepartidor($rut, $agente, (string) $repartidor)] ?? null;

        return $nuevo !== null && $nuevo !== 'N/A' ? $nuevo : $rut;
    }

    /*
     * Operaciones marca con RUT 0-0 a los agentes que no son un proveedor
     * Courier (Envío externo, Latam): lo que entregan no se le paga a nadie.
     */
    public static function sinProveedorCourier(?string $rut): bool
    {
        return $rut !== null && preg_match('/^0+-0$/', self::rut($rut)) === 1;
    }

    /* RUT del cliente según cómo escribe Geolice al comerciante, o null si no está en la hoja Clientes. */
    public function rutCliente(?string $comerciante): ?string
    {
        return $this->clientes[self::claveTexto((string) $comerciante)] ?? null;
    }

    /**
     * @return array{pagar: string, tabla: ?int}|string|null  la llave, AMBIGUA, o null si no hay
     */
    public function buscar(string $rutProveedor, ?string $comerciante, ?string $servicio, string $agente): array|string|null
    {
        $rutCliente = $this->rutCliente($comerciante);
        $codigo = $this->servicios[self::claveTexto((string) $servicio)] ?? null;

        if ($rutCliente === null || $codigo === null) {
            return null;
        }

        $candidatas = $this->llaves[self::rut($rutProveedor) . '|' . $rutCliente . '|' . $codigo] ?? [];

        if ($candidatas === []) {
            return null;
        }

        $claveAgente = self::clave($agente);
        $delAgente = array_filter($candidatas, fn (array $llave) => $llave['agente_clave'] === $claveAgente);

        if ($delAgente !== []) {
            $candidatas = $delAgente;
        }

        /* Dos llaves NO dicen lo mismo aunque tengan tablas distintas. */
        $distintas = [];
        foreach ($candidatas as $llave) {
            $distintas[$llave['pagar'] === 'NO' ? 'NO' : $llave['pagar'] . '|' . $llave['tabla']] = $llave;
        }

        if (count($distintas) !== 1) {
            return self::AMBIGUA;
        }

        $llave = reset($distintas);

        return ['pagar' => $llave['pagar'], 'tabla' => $llave['tabla']];
    }

    /* Nombres de agente y de repartidor: sin espacios de más, minúsculas y sin tildes. */
    public static function clave(?string $texto): string
    {
        return Str::of((string) $texto)->squish()->lower()->ascii()->toString();
    }

    /* Comerciante y servicio: sin espacios de más y en minúsculas. */
    public static function claveTexto(?string $texto): string
    {
        return Str::of((string) $texto)->squish()->lower()->toString();
    }

    public static function claveRepartidor(?string $rut, ?string $agente, ?string $repartidor): string
    {
        return self::rut($rut) . '|' . self::clave($agente) . '|' . self::clave($repartidor);
    }

    /* RUT como lo escribe LogisticaCL: sin puntos, con guion y K mayúscula. */
    public static function rut(?string $rut): string
    {
        return strtoupper(str_replace('.', '', trim((string) $rut)));
    }
}
