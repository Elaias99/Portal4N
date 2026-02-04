<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        $now = now();

        $operadores = [
            'Fabian Pino (Isla)',
            'Sergio Pino (Pto Montt)',
            'Celia Leon (Litoral)',
            'Transporte Mandame',
            'Mercasur',
            'Graciela Burgos (Vallenar)',
            'Patricio Salazar (Iquique)',
            'Hector Rivera (Coquimbo)',
            'ANTARTICA',
            'Claudio Castro (Antofagasta)',
            'Nelson Cisterna (Arica)',
            'Veronica Moya (Balmaceda)',
            'Operador RM',
            'Comercializadora Musso Ltda',
            '4N Temuco',
            'Marcelo Avendaño (Calama)',
            'Graciela Burgos (Copiapo)',
            'Jorge Gonzalez (ptas Arenas)',
            'Cristian Rodriguez (Los Vilos)',
            'Cristian Rodriguez (Ovalle)',
            'Casa Blanca (V Region)',
            'Carlos Villaseca (Talca)',
            'Daphne Katalina Meza Ramos (San Fernando)',
            'Hugo Lopez (Rancagua)',
            'Soc. de inversiones Santa Cruz de Yanguas ltda',
            'Deyna (Valdivia)',
            'Operador Curacavi',
            'Miguel Celis (Curico)',
            '4N TRONCAL SUR 3',
            'Terminal Aereo',
            'Johanna Oyarzo (Osorno)',
        ];

        DB::table('operadores')->insertOrIgnore(
            array_map(fn ($nombre) => [
                'nombre' => $nombre,
                'created_at' => $now,
                'updated_at' => $now,
            ], $operadores)
        );
    }

    public function down(): void
    {
        DB::table('operadores')->whereIn('nombre', [
            'Fabian Pino (Isla)',
            'Sergio Pino (Pto Montt)',
            'Celia Leon (Litoral)',
            'Transporte Mandame',
            'Mercasur',
            'Graciela Burgos (Vallenar)',
            'Patricio Salazar (Iquique)',
            'Hector Rivera (Coquimbo)',
            'ANTARTICA',
            'Claudio Castro (Antofagasta)',
            'Nelson Cisterna (Arica)',
            'Veronica Moya (Balmaceda)',
            'Operador RM',
            'Comercializadora Musso Ltda',
            '4N Temuco',
            'Marcelo Avendaño (Calama)',
            'Graciela Burgos (Copiapo)',
            'Jorge Gonzalez (ptas Arenas)',
            'Cristian Rodriguez (Los Vilos)',
            'Cristian Rodriguez (Ovalle)',
            'Casa Blanca (V Region)',
            'Carlos Villaseca (Talca)',
            'Daphne Katalina Meza Ramos (San Fernando)',
            'Hugo Lopez (Rancagua)',
            'Soc. de inversiones Santa Cruz de Yanguas ltda',
            'Deyna (Valdivia)',
            'Operador Curacavi',
            'Miguel Celis (Curico)',
            '4N TRONCAL SUR 3',
            'Terminal Aereo',
            'Johanna Oyarzo (Osorno)',
        ])->delete();
    }
};
