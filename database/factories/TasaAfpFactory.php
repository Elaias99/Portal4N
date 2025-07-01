<?php

namespace Database\Factories;

use App\Models\TasaAfp;
use App\Models\Afp;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TasaAfp>
 */
class TasaAfpFactory extends Factory
{
    protected $model = TasaAfp::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'id_afp' => Afp::factory(), // crea la AFP automáticamente
            'tasa_cotizacion' => $this->faker->randomFloat(2, 10, 20),
            'tasa_sis' => $this->faker->randomFloat(2, 1, 3),
        ];
    }
}
