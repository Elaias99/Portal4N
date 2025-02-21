<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\Comuna;

class ComunaSeeder extends Seeder
{
    public function run()
    {
        // Insertar o actualizar la XII Región de Magallanes y de la Antártica Chilena
        $region = Region::updateOrCreate(
            ['id' => 15], // Usar el ID correcto de la región
            ['Nombre' => 'XII Región de Magallanes y de la Antártica Chilena', 'Numero' => 12]
        );

        // Insertar o actualizar comunas para la XII Región de Magallanes y de la Antártica Chilena
        $comunas = [
            ['Nombre' => 'Antártica', 'region_id' => 15],
            ['Nombre' => 'Cabo de Hornos', 'region_id' => 15],
            ['Nombre' => 'Laguna Blanca', 'region_id' => 15],
            ['Nombre' => 'Natales', 'region_id' => 15],
            ['Nombre' => 'Porvenir', 'region_id' => 15],
            ['Nombre' => 'Primavera', 'region_id' => 15],
            ['Nombre' => 'Punta Arenas', 'region_id' => 15],
            ['Nombre' => 'Río Verde', 'region_id' => 15],
            ['Nombre' => 'San Gregorio', 'region_id' => 15],
            ['Nombre' => 'Timaukel', 'region_id' => 15],
            ['Nombre' => 'Torres del Paine', 'region_id' => 15],
        ];

        foreach ($comunas as $comunaData) {
            Comuna::updateOrCreate(
                ['Nombre' => $comunaData['Nombre']], // Buscar por nombre de comuna
                $comunaData // Actualizar o crear si no existe
            );
        }
    }
}
