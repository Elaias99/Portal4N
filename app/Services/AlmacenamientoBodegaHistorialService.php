<?php

namespace App\Services;

use App\Models\AlmacenamientoBodega;
use App\Models\AlmacenamientoBodegaHistorial;

class AlmacenamientoBodegaHistorialService
{
    public function registrarCreacion(
        AlmacenamientoBodega $producto
    ): AlmacenamientoBodegaHistorial {
        return $this->registrar($producto, 'CREADO');
    }

    public function registrarEliminacion(
        AlmacenamientoBodega $producto
    ): AlmacenamientoBodegaHistorial {
        return $this->registrar($producto, 'ELIMINADO');
    }

    private function registrar(
        AlmacenamientoBodega $producto,
        string $accion
    ): AlmacenamientoBodegaHistorial {
        return AlmacenamientoBodegaHistorial::create([
            'almacenamiento_bodega_id' => $producto->id,
            'nombre_producto' => $producto->Nombre,
            'accion' => $accion,
        ]);
    }
}