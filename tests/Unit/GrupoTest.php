<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    public function test_instancia_grupo_contiene_sus_atributos(): void
    {
        $grupo = new Grupo([
            'numero_ciclo' => 2,
            'ano'          => 2026,
            'numero_grupo' => 1,
            'abierto'      => true,
        ]);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);
    }

    public function test_grupo_contiene_curso_docentes_y_estudiantes(): void
    {
        $curso = Curso::create([
            'sigla'        => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos'     => 4,
        ]);

        $docente1 = Docente::create([
            'cod_empleado'       => 1001,
            'nombre_empleado'    => 'Ana',
            'apellidos_empleado' => 'Rojas Mora',
        ]);
        $docente2 = Docente::create([
            'cod_empleado'       => 1002,
            'nombre_empleado'    => 'Luis',
            'apellidos_empleado' => 'Vargas Solís',
        ]);

        $grupo = Grupo::create([
            'curso_id'     => $curso->id,
            'numero_ciclo' => 2,
            'ano'          => 2026,
            'numero_grupo' => 1,
            'abierto'      => true,
        ]);
        $grupo->docentes()->attach([$docente1->cod_empleado, $docente2->cod_empleado]);

        for ($i = 1; $i <= 15; $i++) {
            $estudiante = Estudiante::create([
                'carne'                  => 'C' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'nombre'                 => "Estudiante$i",
                'apellidos'              => "Apellido$i",
                'consejero_cod_empleado' => $docente1->cod_empleado,
            ]);
            $grupo->estudiantes()->attach($estudiante->carne, ['nota' => 70 + $i]);
        }

        $grupo = Grupo::with(['curso', 'docentes', 'estudiantes'])->find($grupo->id);

        // Atributos del grupo
        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);

        $this->assertSame('IF6100', $grupo->curso->sigla);

        $this->assertCount(2, $grupo->docentes);
        $this->assertGreaterThanOrEqual(1, $grupo->docentes->count());
        $this->assertLessThanOrEqual(2, $grupo->docentes->count());
        $this->assertTrue($grupo->docentes->contains('cod_empleado', 1001));

        
        $this->assertGreaterThanOrEqual(15, $grupo->estudiantes->count());
        $primero = $grupo->estudiantes->firstWhere('carne', 'C00001');
        $this->assertSame(71.0, $primero->pivot->nota);

     
        $this->assertSame(1001, $primero->consejero->cod_empleado);
    }
}