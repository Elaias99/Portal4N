<?php

namespace Database\Factories;

use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Factories\Factory;

// Importación de los modelos relacionados
use App\Models\Empresa;
use App\Models\Cargo;
use App\Models\Situacion;
use App\Models\EstadoCivil;
use App\Models\Comuna;
use App\Models\Afp;
use App\Models\Salud;
use App\Models\Turno;
use App\Models\SistemaTrabajo;
use App\Models\Banco;
use App\Models\User;
use App\Models\Jefe;
use App\Models\Area;

class TrabajadorFactory extends Factory
{
    protected $model = Trabajador::class;

    public function definition()
    {
        return [
            'Rut' => $this->faker->unique()->numerify('########-#'),
            'Nombre' => $this->faker->firstName,
            'SegundoNombre' => $this->faker->firstName,
            'TercerNombre' => $this->faker->firstName,
            'ApellidoPaterno' => $this->faker->lastName,
            'ApellidoMaterno' => $this->faker->lastName,
            'FechaNacimiento' => $this->faker->date('Y-m-d', '2000-01-01'),
            'Casino' => 'No',

            'fecha_inicio_trabajo' => $this->faker->date('Y-m-d', '2024-05-02'),


            // Relaciones obligatorias
            'empresa_id' => Empresa::factory(),
            'cargo_id' => Cargo::factory(),
            'situacion_id' => Situacion::factory(),
            'estado_civil_id' => EstadoCivil::factory(),
            'comuna_id' => Comuna::factory(),
            'afp_id' => Afp::factory(),
            'salud_id' => Salud::factory(),

            'salario_bruto' => 1000000,
            'calle' => 'Av. Falsa 123',
            'numero_celular' => '123456789',
            'nombre_emergencia'=> 'Juan',
            'contacto_emergencia' => '987654321',
            'CorreoPersonal' => 'example@example.cl',
            'numero_cuenta' => '98541236523',
            'tipo_cuenta' => 'Cta Corriente',

            // Relaciones opcionales
            'turno_id' => Turno::factory(),
            'sistema_trabajo_id' => SistemaTrabajo::factory(),
            'banco_id' => Banco::factory(),
            'user_id' => User::factory(),
            'id_jefe' => Jefe::factory(),
            'area_id' => Area::factory(),
        ];
    }
}
