<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Obtenemos los IDs de situacion y sistema_trabajo correspondientes a "Desvinculado"
        $situacionId = \App\Models\Situacion::where('Nombre', 'Desvinculado')->value('id');
        $sistemaTrabajoId = \App\Models\SistemaTrabajo::where('nombre', 'Desvinculado')->value('id');

        if ($situacionId && $sistemaTrabajoId) {
            $trabajadoresDesvinculados = \App\Models\Trabajador::where('situacion_id', $situacionId)
                ->where('sistema_trabajo_id', $sistemaTrabajoId)
                ->get();

            foreach ($trabajadoresDesvinculados as $trabajador) {
                \App\Models\Desvinculacion::create([
                    'trabajador_id'     => $trabajador->id,
                    'situacion_id'      => $trabajador->situacion_id,
                    'sistema_trabajo_id'=> $trabajador->sistema_trabajo_id,
                    'fecha_desvinculo'  => $trabajador->updated_at ? $trabajador->updated_at->toDateString() : now()->toDateString(),
                    'motivo'            => null, // se puede mejorar luego con campo motivo
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Desvinculacion::truncate();
    }

};
