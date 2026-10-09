<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
     * Cada local OPV pasa a ser su propia ruta.
     *
     * Una OPV con varios locales (por ejemplo Victor Pérez, 7 locales)
     * se separa en una asignación por local, con un solo punto OPV.
     * Así cada local admite por separado inasistencia, cambio de
     * facturación y transportista.
     *
     * La asignación original no se borra ni se modifica su historia:
     * sólo deja de generarse (generar_automaticamente = 0). Sus
     * liquidaciones de meses anteriores quedan intactas.
     */
    public function up(): void
    {
        DB::transaction(function () {
            foreach ($this->opvConVariosLocales(activas: true) as $opv) {
                $puntos = DB::table('suscripcion_opv_puntos')
                    ->where('suscripcion_asignacion_id', $opv->id)
                    ->orderBy('id')
                    ->get();

                foreach ($puntos as $punto) {
                    $asignacionId = DB::table('suscripcion_asignaciones')
                        ->insertGetId([
                            'suscripcion_proveedor_id' => $opv->suscripcion_proveedor_id,
                            'suscripcion_transportista_id' => $opv->suscripcion_transportista_id,
                            'suscripcion_zona_id' => $opv->suscripcion_zona_id,
                            'punto_1' => $opv->punto_1,
                            'origen_gasto' => 'OPV',
                            'punto_2' => $opv->punto_2,
                            'codigo' => $this->codigoLocal($punto),
                            'servicio' => $this->servicioLocal($punto),
                            'costo' => $opv->costo,
                            'grupo_prefactura' => $opv->grupo_prefactura,
                            'generar_automaticamente' => $opv->generar_automaticamente,
                            'tipo_asignacion' => 'RUTA',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    DB::table('suscripcion_opv_puntos')->insert([
                        'suscripcion_asignacion_id' => $asignacionId,
                        'ruta_nombre' => $punto->ruta_nombre,
                        'local' => $punto->local,
                        'nombre_local' => $punto->nombre_local,
                        'nombre_local_corto' => $punto->nombre_local_corto,
                        'direccion' => $punto->direccion,
                        'comuna' => $punto->comuna,
                        'lat' => $punto->lat,
                        'lng' => $punto->lng,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('suscripcion_asignaciones')
                    ->where('id', $opv->id)
                    ->update([
                        'generar_automaticamente' => 0,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /*
     * Vuelve a la OPV con varios locales, siempre que las rutas por
     * local todavía no tengan liquidaciones, novedades ni excepciones.
     */
    public function down(): void
    {
        DB::transaction(function () {
            foreach ($this->opvConVariosLocales(activas: false) as $opv) {
                $puntos = DB::table('suscripcion_opv_puntos')
                    ->where('suscripcion_asignacion_id', $opv->id)
                    ->get();

                $idsPorLocal = DB::table('suscripcion_asignaciones')
                    ->where('id', '>', $opv->id)
                    ->where('suscripcion_proveedor_id', $opv->suscripcion_proveedor_id)
                    ->where('tipo_asignacion', 'RUTA')
                    ->where('origen_gasto', 'OPV')
                    ->whereIn('codigo', $puntos->map(fn ($punto) => $this->codigoLocal($punto))->all())
                    ->pluck('id');

                if ($idsPorLocal->isEmpty()) {
                    continue;
                }

                foreach (['suscripcion_liquidacion_detalles', 'suscripcion_ajustes_mensuales', 'suscripcion_excepciones_facturacion'] as $tabla) {
                    if (DB::table($tabla)->whereIn('suscripcion_asignacion_id', $idsPorLocal)->exists()) {
                        throw new RuntimeException(
                            "No se puede revertir: las rutas OPV por local de {$opv->codigo} ya tienen registros en {$tabla}."
                        );
                    }
                }

                DB::table('suscripcion_opv_puntos')
                    ->whereIn('suscripcion_asignacion_id', $idsPorLocal)
                    ->delete();

                DB::table('suscripcion_asignaciones')
                    ->whereIn('id', $idsPorLocal)
                    ->delete();

                DB::table('suscripcion_asignaciones')
                    ->where('id', $opv->id)
                    ->update([
                        'generar_automaticamente' => null,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /*
     * OPV (mismo criterio que la generación mensual) de tipo RUTA
     * con más de un local. activas = se generan automáticamente.
     */
    private function opvConVariosLocales(bool $activas): Collection
    {
        return DB::table('suscripcion_asignaciones as a')
            ->where('a.tipo_asignacion', 'RUTA')
            ->where(function ($query) use ($activas) {
                if ($activas) {
                    $query->whereNull('a.generar_automaticamente')
                        ->orWhere('a.generar_automaticamente', 1);
                } else {
                    $query->where('a.generar_automaticamente', 0);
                }
            })
            ->where(function ($query) {
                $query->whereRaw("UPPER(TRIM(a.codigo)) = 'OPV'")
                    ->orWhereRaw("UPPER(TRIM(a.codigo)) LIKE '%.OPV'")
                    ->orWhereRaw("UPPER(TRIM(a.servicio)) = 'OPV'")
                    ->orWhereRaw("UPPER(TRIM(a.origen_gasto)) = 'OPV'");
            })
            ->whereRaw(
                '(SELECT COUNT(*) FROM suscripcion_opv_puntos o WHERE o.suscripcion_asignacion_id = a.id) > 1'
            )
            ->orderBy('a.id')
            ->select('a.*')
            ->get();
    }

    /*
     * Ejemplo: LOS TRAPENSES.OPV
     */
    private function codigoLocal(object $punto): string
    {
        $nombre = trim((string) (
            $punto->nombre_local_corto
            ?: $punto->nombre_local
            ?: $punto->local
        ));

        return mb_strtoupper($nombre) . '.OPV';
    }

    /*
     * Ejemplo: OPV UNIMARC LOS TRAPENSES
     * Es el texto que aparece en la línea de la pre-factura.
     */
    private function servicioLocal(object $punto): string
    {
        $nombre = trim((string) (
            $punto->nombre_local
            ?: $punto->nombre_local_corto
            ?: $punto->local
        ));

        return 'OPV ' . $nombre;
    }
};
