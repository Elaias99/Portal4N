<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'Nombre' => $this->faker->company,
            'giro' => $this->faker->words(2, true),
            'direccion' => $this->faker->address,
            'cta_corriente' => $this->faker->numerify('##########'),
            'mail_formalizado' => $this->faker->companyEmail,
            'banco_id' => null,      // no obligatorio
            'comuna_id' => null,     // no obligatorio
            'logo' => null,
        ];
    }
}
