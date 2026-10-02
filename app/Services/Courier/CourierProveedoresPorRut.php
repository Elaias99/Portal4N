<?php

namespace App\Services\Courier;

use App\Models\CourierProveedor;
use Illuminate\Support\Facades\DB;

/*
 * Encuentra el proveedor del catálogo (DatosProveedores) por RUT, para
 * los pagos que no salen de un bulto: Acuerdos, Ruta CV, Servicios,
 * Visitas, Especiales y Apoyo Alza.
 *
 * Un RUT puede tener varias filas en el catálogo (una por operador y
 * usuario); se toma la primera. La zona sale de las comunas del agente
 * de ese operador, sólo si todas son de la misma zona.
 */
class CourierProveedoresPorRut
{
    /** @var array<string, array{id: int, zona: ?string}>|null */
    private ?array $porRut = null;

    /** @return array{id: int, zona: ?string}|null */
    public function buscar(?string $rut): ?array
    {
        $clave = self::normalizar($rut);

        return $clave === '' ? null : ($this->cargar()[$clave] ?? null);
    }

    public static function normalizar(?string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9kK]/', '', (string) $rut) ?? '');
    }

    private function cargar(): array
    {
        if ($this->porRut !== null) {
            return $this->porRut;
        }

        $zonas = DB::table('courier_cobertura_comunas')
            ->join('courier_agentes', 'courier_agentes.id', '=', 'courier_cobertura_comunas.courier_agente_id')
            ->whereNotNull('courier_cobertura_comunas.zona')
            ->select('courier_agentes.nombre', 'courier_cobertura_comunas.zona')
            ->distinct()
            ->get()
            ->groupBy(fn ($fila) => mb_strtolower(trim($fila->nombre)))
            ->map(fn ($filas) => $filas->pluck('zona')->unique()->count() === 1 ? $filas->first()->zona : null);

        $this->porRut = [];

        CourierProveedor::query()
            ->whereNotNull('rut')
            ->orderBy('id')
            ->get(['id', 'rut', 'operador'])
            ->each(function (CourierProveedor $proveedor) use ($zonas) {
                $this->porRut[self::normalizar($proveedor->rut)] ??= [
                    'id' => $proveedor->id,
                    'zona' => $zonas[mb_strtolower(trim((string) $proveedor->operador))] ?? null,
                ];
            });

        return $this->porRut;
    }
}
