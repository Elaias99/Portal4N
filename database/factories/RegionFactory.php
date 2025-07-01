<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'Nombre' => $this->faker->state,
            'Numero' => $this->faker->randomElement(['1', '2', '3', '4']),
            'Abreviatura' => $this->faker->randomElement(['I', 'II', 'III', 'IV']),
            'NumeroRomano' => $this->faker->randomElement(['TPCA', 'ANTOF', 'ATCMA', 'COQ']),

        ];
    }
}
