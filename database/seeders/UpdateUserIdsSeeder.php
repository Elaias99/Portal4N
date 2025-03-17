<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateUserIdsSeeder extends Seeder
{
    
    public function run()
    {
        // Desactivar temporalmente las claves foráneas (aunque no existan, por seguridad)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Obtener todos los IDs actuales de la tabla tallas, ordenados
        $tallas = DB::table('tallas')->orderBy('id')->get(['id']);

        // Crear un mapeo de ID antiguo -> nuevo ID consecutivo
        $newId = 1;
        $idMapping = [];

        foreach ($tallas as $talla) {
            $idMapping[$talla->id] = $newId;
            $newId++;
        }

        // Actualizar los IDs en la tabla tallas
        foreach ($idMapping as $oldId => $newId) {
            DB::table('tallas')->where('id', $oldId)->update(['id' => $newId]);
        }

        // Reactivar la verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('IDs en tallas actualizados correctamente.');
    }



}
