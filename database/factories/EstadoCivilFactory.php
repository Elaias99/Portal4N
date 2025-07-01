<?php

namespace Database\Factories;

use App\Models\EstadoCivil;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstadoCivilFactory extends Factory
{
    protected $model = EstadoCivil::class;

    public function definition(): array
    {
        return [
            'Nombre' => $this->faker->randomElement(['Soltero', 'Casado', 'Divorciado']),
        ];
    }
}
