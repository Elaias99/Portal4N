<?php

namespace Database\Factories;

use App\Models\Situacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class SituacionFactory extends Factory
{
    protected $model = Situacion::class;

    public function definition(): array
    {
        return [
            'Nombre' => $this->faker->randomElement(['Activo', 'Suspendido', 'Desvinculado']),
        ];
    }
}
