<?php

namespace Database\Factories;

use App\Models\Comuna;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComunaFactory extends Factory
{
    protected $model = Comuna::class;

    public function definition(): array
    {
        return [
            'Nombre' => $this->faker->city,
            'region_id' => Region::factory(),

        ];
    }
}
