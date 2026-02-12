<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalendarioPagosServiciosSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar solo registros 2026 si existieran
        DB::table('calendario_pagos_servicios')
            ->where('anio', 2026)
            ->delete();

        $data = [

            // =========================
            // ENERO 2026
            // =========================
            [2026, 1, 'COLABORADORES', null, '2026-01-30'],
            [2026, 1, 'AGENCIAS', null, '2026-02-13'],
            [2026, 1, 'COURIER', 15, '2026-02-13'],
            [2026, 1, 'COURIER', 30, '2026-02-27'],
            [2026, 1, 'SUSCRIPCIONES', null, '2026-02-20'],

            // FEBRERO 2026
            [2026, 2, 'COLABORADORES', null, '2026-02-27'],
            [2026, 2, 'AGENCIAS', null, '2026-03-13'],
            [2026, 2, 'COURIER', 15, '2026-03-13'],
            [2026, 2, 'COURIER', 30, '2026-03-31'],
            [2026, 2, 'SUSCRIPCIONES', null, '2026-03-20'],

            // MARZO 2026
            [2026, 3, 'COLABORADORES', null, '2026-03-31'],
            [2026, 3, 'AGENCIAS', null, '2026-04-17'],
            [2026, 3, 'COURIER', 15, '2026-04-17'],
            [2026, 3, 'COURIER', 30, '2026-04-30'],
            [2026, 3, 'SUSCRIPCIONES', null, '2026-04-24'],

            // ABRIL 2026
            [2026, 4, 'COLABORADORES', null, '2026-04-30'],
            [2026, 4, 'AGENCIAS', null, '2026-05-15'],
            [2026, 4, 'COURIER', 15, '2026-05-15'],
            [2026, 4, 'COURIER', 30, '2026-05-29'],
            [2026, 4, 'SUSCRIPCIONES', null, '2026-05-22'],

            // MAYO 2026
            [2026, 5, 'COLABORADORES', null, '2026-05-29'],
            [2026, 5, 'AGENCIAS', null, '2026-06-12'],
            [2026, 5, 'COURIER', 15, '2026-06-12'],
            [2026, 5, 'COURIER', 30, '2026-06-30'],
            [2026, 5, 'SUSCRIPCIONES', null, '2026-06-19'],

            // JUNIO 2026
            [2026, 6, 'COLABORADORES', null, '2026-06-30'],
            [2026, 6, 'AGENCIAS', null, '2026-07-17'],
            [2026, 6, 'COURIER', 15, '2026-07-17'],
            [2026, 6, 'COURIER', 30, '2026-07-31'],
            [2026, 6, 'SUSCRIPCIONES', null, '2026-07-24'],

            // JULIO 2026
            [2026, 7, 'COLABORADORES', null, '2026-07-31'],
            [2026, 7, 'AGENCIAS', null, '2026-08-14'],
            [2026, 7, 'COURIER', 15, '2026-08-14'],
            [2026, 7, 'COURIER', 30, '2026-08-28'],
            [2026, 7, 'SUSCRIPCIONES', null, '2026-08-21'],

            // AGOSTO 2026
            [2026, 8, 'COLABORADORES', null, '2026-08-28'],
            [2026, 8, 'AGENCIAS', null, '2026-09-15'],
            [2026, 8, 'COURIER', 15, '2026-09-15'],
            [2026, 8, 'COURIER', 30, '2026-09-30'],
            [2026, 8, 'SUSCRIPCIONES', null, '2026-09-17'],

            // SEPTIEMBRE 2026
            [2026, 9, 'COLABORADORES', null, '2026-09-30'],
            [2026, 9, 'AGENCIAS', null, '2026-10-16'],
            [2026, 9, 'COURIER', 15, '2026-10-16'],
            [2026, 9, 'COURIER', 30, '2026-10-30'],
            [2026, 9, 'SUSCRIPCIONES', null, '2026-10-23'],

            // OCTUBRE 2026
            [2026, 10, 'COLABORADORES', null, '2026-10-30'],
            [2026, 10, 'AGENCIAS', null, '2026-11-13'],
            [2026, 10, 'COURIER', 15, '2026-11-13'],
            [2026, 10, 'COURIER', 30, '2026-11-30'],
            [2026, 10, 'SUSCRIPCIONES', null, '2026-11-20'],

            // NOVIEMBRE 2026
            [2026, 11, 'COLABORADORES', null, '2026-11-30'],
            [2026, 11, 'AGENCIAS', null, '2026-12-18'],
            [2026, 11, 'COURIER', 15, '2026-12-18'],
            [2026, 11, 'COURIER', 30, '2026-12-31'],
            [2026, 11, 'SUSCRIPCIONES', null, '2026-12-24'],

            // DICIEMBRE 2026
            [2026, 12, 'COLABORADORES', null, '2026-12-31'],
            [2026, 12, 'AGENCIAS', null, '2027-01-15'],
            [2026, 12, 'COURIER', 15, '2027-01-15'],
            [2026, 12, 'COURIER', 30, '2027-01-30'],
            [2026, 12, 'SUSCRIPCIONES', null, '2027-01-23'],
        ];

        foreach ($data as $row) {
            DB::table('calendario_pagos_servicios')->insert([
                'anio'       => $row[0],
                'mes'        => $row[1],
                'servicio'   => $row[2],
                'creditos'   => $row[3],
                'fecha_pago' => $row[4],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
