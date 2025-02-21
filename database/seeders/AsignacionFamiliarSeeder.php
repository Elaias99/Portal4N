<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AsignacionFamiliar;

class AsignacionFamiliarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        AsignacionFamiliar::create([
            'tramo' => 'A',
            'salario_minimo' => 0,
            'salario_maximo' => 586227,
            'monto' => 21243,
        ]);

        AsignacionFamiliar::create([
            'tramo' => 'B',
            'salario_minimo' => 586228,
            'salario_maximo' => 856247,
            'monto' => 13036,
        ]);

        AsignacionFamiliar::create([
            'tramo' => 'C',
            'salario_minimo' => 856248,
            'salario_maximo' => 1335450,
            'monto' => 4119,
        ]);

        AsignacionFamiliar::create([
            'tramo' => 'D',
            'salario_minimo' => 1335451,
            'salario_maximo' => 999999999.99,  // Ajusta a un valor más manejable
            'monto' => 0,
        ]);
    }
}
