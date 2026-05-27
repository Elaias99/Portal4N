<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLA = 'factories';
    private const COLUMNA_DOCUMENTO = 'documento_financiero_id';

    /*
    |--------------------------------------------------------------------------
    | Índice normal para la nueva relación uno-a-muchos
    |--------------------------------------------------------------------------
    | Se utiliza un nombre propio de esta migración para poder retirarlo
    | correctamente en rollback sin tocar índices anteriores de la tabla.
    |--------------------------------------------------------------------------
    */
    private const INDICE_MULTIPLE = 'factories_documento_financiero_id_multi_index';

    /*
    |--------------------------------------------------------------------------
    | Índice único para rollback
    |--------------------------------------------------------------------------
    | Solo se volverá a crear si no existen documentos con más de un Factoring.
    |--------------------------------------------------------------------------
    */
    private const INDICE_UNICO_ROLLBACK = 'factories_documento_financiero_id_unique';

    /**
     * Permitir múltiples registros Factoring por documento financiero.
     */
    public function up(): void
    {
        $indiceUnicoActual = $this->obtenerIndiceUnicoSimpleDocumento();

        /*
        |--------------------------------------------------------------------------
        | Sin índice único no hay nada que liberar
        |--------------------------------------------------------------------------
        | Esto permite ejecutar la migración de forma segura aunque el esquema
        | ya haya sido ajustado previamente en otro entorno.
        |--------------------------------------------------------------------------
        */
        if (!$indiceUnicoActual) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Crear índice normal antes de eliminar el UNIQUE
        |--------------------------------------------------------------------------
        | documento_financiero_id seguirá indexado para búsquedas, relaciones
        | Eloquent y cualquier clave foránea que dependa de esta columna.
        |--------------------------------------------------------------------------
        */
        if (!$this->existeIndiceNoUnicoDocumento()) {
            Schema::table(self::TABLA, function (Blueprint $table) {
                $table->index(
                    self::COLUMNA_DOCUMENTO,
                    self::INDICE_MULTIPLE
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Retirar restricción que impedía múltiples Factorings
        |--------------------------------------------------------------------------
        */
        DB::statement(
            'ALTER TABLE `' . self::TABLA . '` DROP INDEX `' .
            str_replace('`', '``', $indiceUnicoActual) . '`'
        );
    }

    /**
     * Restaurar la unicidad anterior.
     *
     * Este rollback no puede ejecutarse si ya existen documentos con más de
     * un registro Factoring, porque la base de datos no podría crear el UNIQUE.
     */
    public function down(): void
    {
        $documentoDuplicado = DB::table(self::TABLA)
            ->select(self::COLUMNA_DOCUMENTO)
            ->groupBy(self::COLUMNA_DOCUMENTO)
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($documentoDuplicado) {
            throw new RuntimeException(
                'No se puede revertir la migración de factories: existen documentos financieros con más de un registro Factoring asociado.'
            );
        }

        if (!$this->obtenerIndiceUnicoSimpleDocumento()) {
            Schema::table(self::TABLA, function (Blueprint $table) {
                $table->unique(
                    self::COLUMNA_DOCUMENTO,
                    self::INDICE_UNICO_ROLLBACK
                );
            });
        }

        if ($this->existeIndicePorNombre(self::INDICE_MULTIPLE)) {
            Schema::table(self::TABLA, function (Blueprint $table) {
                $table->dropIndex(self::INDICE_MULTIPLE);
            });
        }
    }

    /**
     * Obtener el nombre real del índice UNIQUE simple aplicado únicamente
     * sobre documento_financiero_id.
     */
    private function obtenerIndiceUnicoSimpleDocumento(): ?string
    {
        $indice = DB::selectOne(
            '
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND NON_UNIQUE = 0
                  AND INDEX_NAME <> "PRIMARY"
                GROUP BY INDEX_NAME
                HAVING COUNT(*) = 1
                   AND MAX(CASE WHEN COLUMN_NAME = ? THEN 1 ELSE 0 END) = 1
                LIMIT 1
            ',
            [
                self::TABLA,
                self::COLUMNA_DOCUMENTO,
            ]
        );

        return $indice?->INDEX_NAME ?? null;
    }

    /**
     * Determinar si ya existe un índice no único utilizable cuyo primer campo
     * sea documento_financiero_id.
     */
    private function existeIndiceNoUnicoDocumento(): bool
    {
        $indice = DB::selectOne(
            '
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND SEQ_IN_INDEX = 1
                  AND NON_UNIQUE = 1
                LIMIT 1
            ',
            [
                self::TABLA,
                self::COLUMNA_DOCUMENTO,
            ]
        );

        return $indice !== null;
    }

    /**
     * Consultar existencia de un índice específico por nombre.
     */
    private function existeIndicePorNombre(string $nombreIndice): bool
    {
        $indice = DB::selectOne(
            '
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND INDEX_NAME = ?
                LIMIT 1
            ',
            [
                self::TABLA,
                $nombreIndice,
            ]
        );

        return $indice !== null;
    }
};