<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sigla' => fake()->unique()->bothify('IF####'),
            'nombre_curso' => fake()->sentence(3),
            'creditos' => fake()->numberBetween(1, 6),
        ];
    }
}
