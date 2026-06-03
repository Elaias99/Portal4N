<?php

namespace App\Services\Ventas\Factoring;

use App\Models\CesionFactory;
use App\Models\Factory as FactoryRegistro;

class CesionFactoryService
{
    /**
     * Resolver o crear cabecera de cesión.
     *
     * La cesión se identifica inicialmente por número de cesión + banco.
     * Esto evita mezclar registros si por error existe la misma cesión
     * con distintas entidades Factoring/Banco.
     */
    public function resolverOCrear(array $datos): CesionFactory
    {
        $cesion = trim((string) ($datos['cesion'] ?? ''));
        $bancoId = (int) ($datos['banco_id'] ?? 0);

        $cesionFactory = CesionFactory::where('cesion', $cesion)
            ->where('banco_id', $bancoId)
            ->lockForUpdate()
            ->first();

        if ($cesionFactory) {
            return $cesionFactory;
        }

        return CesionFactory::create([
            'cesion' => $cesion,
            'banco_id' => $bancoId,
            'fecha_operacion' => $datos['fecha_operacion'],
            'comision_total' => (int) ($datos['comision_total'] ?? 0),
            'monto_a_recibir' => $datos['monto_a_recibir'] ?? null,
            'estado_operacion' => $datos['estado_operacion'] ?? 'Vigente',
            'user_id' => $datos['user_id'] ?? null,
        ]);
    }

    /**
     * Asociar un registro Factoring operativo a una cabecera de cesión.
     */
    public function asociarFactory(
        FactoryRegistro $factory,
        CesionFactory $cesionFactory
    ): FactoryRegistro {
        if ((int) $factory->cesion_factoring_id === (int) $cesionFactory->id) {
            return $factory;
        }

        $factory->update([
            'cesion_factoring_id' => $cesionFactory->id,
        ]);

        return $factory->refresh();
    }

    /**
     * Sincronizar estado general de la cesión.
     *
     * Regla:
     * - Si al menos un movimiento hijo está Vigente, la cesión queda Vigente.
     * - Si todos los movimientos hijos están Cerrada, la cesión queda Cerrada.
     */
    public function sincronizarEstadoOperacion(CesionFactory $cesionFactory): CesionFactory
    {
        $factories = $cesionFactory->factories()
            ->lockForUpdate()
            ->get();

        if ($factories->isEmpty()) {
            return $cesionFactory;
        }

        $hayVigente = $factories->contains(
            fn ($factory) => $factory->estado_operacion === 'Vigente'
        );

        $nuevoEstado = $hayVigente
            ? 'Vigente'
            : 'Cerrada';

        if ($cesionFactory->estado_operacion !== $nuevoEstado) {
            $cesionFactory->update([
                'estado_operacion' => $nuevoEstado,
            ]);
        }

        return $cesionFactory->refresh();
    }
}