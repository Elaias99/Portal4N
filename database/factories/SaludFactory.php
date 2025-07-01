<?php

namespace Database\Factories;

use App\Models\Salud;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaludFactory extends Factory
{
    protected $model = Salud::class;

    public function definition(): array
    {
        return [
            'Nombre' => $this->faker->randomElement(['Fonasa', 'Colmena', 'Banmédica']),
        ];
    }
}
