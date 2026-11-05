<?php

namespace Database\Factories;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cod_empleado' => fake()->unique()->numberBetween(1000, 999999),
            'nombre_empleado' => fake()->firstName(),
            'apellidos_empleado' => fake()->lastName().' '.fake()->lastName(),
        ];
    }
}
