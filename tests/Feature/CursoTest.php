<?php

namespace Tests\Feature;

use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursoTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_crea_curso_y_lo_guarda_en_la_base_de_datos(): void
    {
        $datos = [
            'sigla'        => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos'     => 4,
        ];

        $respuesta = $this->postJson(route('cursos.store'), $datos);

        $respuesta->assertStatus(201)
                  ->assertJsonPath('curso.sigla', 'IF6100');

        $this->assertDatabaseHas('cursos', $datos);
        $this->assertDatabaseCount('cursos', 1);
        $this->assertSame(1, Curso::where('sigla', 'IF6100')->count());
    }

    public function test_store_rechaza_datos_incompletos(): void
    {
        $this->postJson(route('cursos.store'), [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['sigla', 'nombre_curso', 'creditos']);

        $this->assertDatabaseCount('cursos', 0);
    }

    public function test_store_rechaza_sigla_duplicada(): void
    {
        Curso::create([
            'sigla'        => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos'     => 4,
        ]);

        $this->postJson(route('cursos.store'), [
            'sigla'        => 'IF6100',
            'nombre_curso' => 'Otro curso',
            'creditos'     => 3,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['sigla']);

        $this->assertDatabaseCount('cursos', 1);
    }
    
}