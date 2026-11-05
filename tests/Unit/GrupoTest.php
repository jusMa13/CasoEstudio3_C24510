<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    public function test_instancia_grupo_contiene_sus_atributos(): void
    {
        $grupo = new Grupo([
            'numero_ciclo' => 2,
            'ano' => 2026,
            'numero_grupo' => 1,
            'abierto' => true,
        ]);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);
    }

    public function test_grupo_contiene_curso_docentes_y_estudiantes(): void
    {
        $curso = Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        $docente1 = Docente::create([
            'cod_empleado' => 1001,
            'nombre_empleado' => 'Ana',
            'apellidos_empleado' => 'Rojas Mora',
        ]);
        $docente2 = Docente::create([
            'cod_empleado' => 1002,
            'nombre_empleado' => 'Luis',
            'apellidos_empleado' => 'Vargas Solís',
        ]);

        $grupo = Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'ano' => 2026,
            'numero_grupo' => 1,
            'abierto' => true,
        ]);
        $grupo->docentes()->attach([$docente1->cod_empleado, $docente2->cod_empleado]);

        for ($i = 1; $i <= 15; $i++) {
            $estudiante = Estudiante::create([
                'carne' => 'C'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'nombre' => "Estudiante$i",
                'apellidos' => "Apellido$i",
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

    public function test_cp01_recupera_el_grupo_persistido_con_su_curso(): void
    {
        $curso = $this->crearCurso();
        $grupo = $this->crearGrupo($curso);

        $grupo = Grupo::find($grupo->id);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertIsBool($grupo->abierto);
        $this->assertTrue($grupo->abierto);
        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertSame(4, $grupo->curso->creditos);
        $this->assertDatabaseCount('grupos', 1);
    }

    public function test_cp02_un_curso_consulta_sus_grupos_y_distingue_los_abiertos(): void
    {
        $curso = $this->crearCurso();
        $this->crearGrupo($curso, true, 1);
        $this->crearGrupo($curso, true, 2);
        $this->crearGrupo($curso, false, 3);

        $grupos = $curso->grupos;
        $gruposAbiertos = $curso->grupos()->where('abierto', true)->get();

        $this->assertCount(3, $grupos);
        $this->assertCount(2, $gruposAbiertos);
        $this->assertEqualsCanonicalizing([1, 2], $gruposAbiertos->pluck('numero_grupo')->all());
        foreach ($grupos as $grupo) {
            $this->assertSame($curso->id, $grupo->curso_id);
        }
    }

    public function test_cp03_asigna_hasta_dos_docentes_y_expone_la_relacion_inversa(): void
    {
        $curso = $this->crearCurso();
        $grupo = $this->crearGrupo($curso);
        $docente1 = $this->crearDocente(1001);
        $docente2 = $this->crearDocente(1002);

        $grupo->asignarDocente($docente1->cod_empleado);
        $grupo->asignarDocente($docente2->cod_empleado);

        $this->assertCount(2, $grupo->docentes);
        $this->assertTrue($grupo->docentes->contains('cod_empleado', 1001));
        $this->assertTrue($grupo->docentes->contains('cod_empleado', 1002));
        $this->assertTrue($docente1->grupos()->whereKey($grupo->id)->exists());
        $this->assertDatabaseCount('docente_grupo', 2);
    }

    public function test_cp04_rechaza_un_tercer_docente_y_conserva_los_dos_asignados(): void
    {
        $curso = $this->crearCurso();
        $grupo = $this->crearGrupo($curso);
        $docente1 = $this->crearDocente(1001);
        $docente2 = $this->crearDocente(1002);
        $docente3 = $this->crearDocente(1003);
        $grupo->asignarDocente($docente1->cod_empleado);
        $grupo->asignarDocente($docente2->cod_empleado);

        expect(fn () => $grupo->asignarDocente($docente3->cod_empleado))
            ->toThrow(DomainException::class);

        $this->assertCount(2, $grupo->fresh()->docentes);
        $this->assertDatabaseCount('docente_grupo', 2);
        $this->assertDatabaseMissing('docente_grupo', [
            'grupo_id' => $grupo->id,
            'docente_cod_empleado' => 1003,
        ]);
    }

    public function test_cp05_no_abre_un_grupo_con_catorce_estudiantes(): void
    {
        $grupo = $this->crearGrupoParaApertura(14);

        $this->assertFalse($grupo->puedeAbrirse());
        expect(fn () => $grupo->abrir())->toThrow(DomainException::class);

        $this->assertFalse($grupo->fresh()->abierto);
    }

    public function test_cp06_abre_un_grupo_con_quince_estudiantes_y_un_docente(): void
    {
        $grupo = $this->crearGrupoParaApertura(15);

        $this->assertTrue($grupo->puedeAbrirse());

        $grupo->abrir();

        $this->assertTrue($grupo->fresh()->abierto);
        $this->assertDatabaseHas('grupos', [
            'id' => $grupo->id,
            'abierto' => true,
        ]);
    }

    private function crearCurso(): Curso
    {
        return Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);
    }

    private function crearGrupo(Curso $curso, bool $abierto = true, int $numeroGrupo = 1): Grupo
    {
        return Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'ano' => 2026,
            'numero_grupo' => $numeroGrupo,
            'abierto' => $abierto,
        ]);
    }

    private function crearDocente(int $codigo): Docente
    {
        return Docente::factory()->create(['cod_empleado' => $codigo]);
    }

    private function crearGrupoParaApertura(int $cantidadEstudiantes): Grupo
    {
        $grupo = $this->crearGrupo($this->crearCurso(), false);
        $docente = $this->crearDocente(1001);
        $grupo->asignarDocente($docente->cod_empleado);
        $this->crearEstudiantes($grupo, $docente, $cantidadEstudiantes);

        return $grupo;
    }

    private function crearEstudiantes(Grupo $grupo, Docente $docente, int $cantidad): void
    {
        $estudiantes = Estudiante::factory()
            ->count($cantidad)
            ->recycle($docente)
            ->create();

        $grupo->estudiantes()->attach($estudiantes->modelKeys());
    }
}
