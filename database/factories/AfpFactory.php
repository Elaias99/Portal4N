<?php

namespace Database\Factories;

use App\Models\AFP;
use Illuminate\Database\Eloquent\Factories\Factory;

class AfpFactory extends Factory
{
    protected $model = AFP::class;

    public function definition(): array
    {
        return [
            'Nombre' => $this->faker->company . ' AFP',
        ];
    }
}
