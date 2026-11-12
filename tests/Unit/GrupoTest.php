<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('una instancia de grupo conserva sus atributos tipados', function () {
    $grupo = new Grupo([
        'numero_ciclo' => 2,
        'ano' => 2026,
        'numero_grupo' => 1,
        'abierto' => true,
    ]);

    expect($grupo->numero_ciclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numero_grupo)->toBe(1)
        ->and($grupo->abierto)->toBeBool();
});

test('un grupo persiste sus atributos asignables y los recupera con sus tipos', function () {
    $curso = grupoTestCrearCurso();

    $grupo = Grupo::create([
        'curso_id' => $curso->id,
        'numero_ciclo' => '2',
        'ano' => '2026',
        'numero_grupo' => '1',
        'abierto' => 1,
    ])->fresh();

    expect($grupo->curso_id)->toBe($curso->id)
        ->and($grupo->numero_ciclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numero_grupo)->toBe(1)
        ->and($grupo->abierto)->toBeBool()
        ->and($grupo->abierto)->toBeTrue();
});

test('un grupo carga su curso, docentes, estudiantes, notas y consejero', function () {
    $curso = grupoTestCrearCurso();
    $docente = grupoTestCrearDocente(
        1001,
        'Ana',
        'Mora Vargas'
    );
    $segundoDocente = grupoTestCrearDocente(
        1002,
        'Luis',
        'Rojas Solano'
    );
    $grupo = grupoTestCrearGrupo($curso, true);
    $grupo->asignarDocente($docente);
    $grupo->asignarDocente($segundoDocente);
    $estudiantes = Estudiante::factory()
        ->count(15)
        ->recycle($docente)
        ->create();

    foreach ($estudiantes as $indice => $estudiante) {
        $grupo->estudiantes()->attach($estudiante->carne, ['nota' => 71 + $indice]);
    }

    $grupo = Grupo::with(['curso', 'docentes', 'estudiantes'])->findOrFail($grupo->id);
    $primerEstudiante = $grupo->estudiantes->first();

    expect($grupo->numero_ciclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numero_grupo)->toBe(1)
        ->and($grupo->abierto)->toBeBool()
        ->and($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('cod_empleado')->all())->toContain(1001)
        ->and($grupo->estudiantes)->toHaveCount(15)
        ->and($primerEstudiante->pivot->nota)->toBeFloat()
        ->and($primerEstudiante->consejero->cod_empleado)->toBe(1001);
});

test('CP-01 recupera un grupo persistido con sus atributos y su curso', function () {
    $curso = grupoTestCrearCurso();
    $grupo = grupoTestCrearGrupo($curso, true);

    $grupo = Grupo::findOrFail($grupo->id);

    expect($grupo->numero_ciclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numero_grupo)->toBe(1)
        ->and($grupo->abierto)->toBeBool()
        ->and($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->curso->creditos)->toBe(4)
        ->and(Grupo::count())->toBe(1);
});

test('CP-02 consulta varios grupos del curso y distingue los abiertos', function () {
    $curso = grupoTestCrearCurso();
    grupoTestCrearGrupo($curso, true, 1);
    grupoTestCrearGrupo($curso, true, 2);
    grupoTestCrearGrupo($curso, false, 3);

    $grupos = $curso->grupos;
    $gruposAbiertos = $curso->grupos()->where('abierto', true)->get();

    expect($grupos)->toHaveCount(3)
        ->and($gruposAbiertos->pluck('numero_grupo')->sort()->values()->all())->toBe([1, 2])
        ->and($grupos->every(fn (Grupo $grupo) => $grupo->curso_id === $curso->id))->toBeTrue();
});

test('CP-03 asigna dos docentes y mantiene la relación inversa', function () {
    $grupo = grupoTestCrearGrupo(grupoTestCrearCurso());
    $docente1 = grupoTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $docente2 = grupoTestCrearDocente(1002, 'Luis', 'Rojas Solano');

    $grupo->asignarDocente($docente1);
    $grupo->asignarDocente($docente2);

    expect($grupo->fresh()->docentes)->toHaveCount(2)
        ->and($grupo->fresh()->docentes->pluck('cod_empleado')->all())->toContain(1001, 1002)
        ->and($docente1->grupos()->whereKey($grupo->id)->exists())->toBeTrue()
        ->and(DB::table('grupo_docente')->count())->toBe(2);
});

test('CP-04 rechaza un tercer docente y conserva los dos asignados', function () {
    $grupo = grupoTestCrearGrupo(grupoTestCrearCurso());
    $docente1 = grupoTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $docente2 = grupoTestCrearDocente(1002, 'Luis', 'Rojas Solano');
    $docente3 = grupoTestCrearDocente(1003, 'Carla', 'Jiménez Araya');
    $grupo->asignarDocente($docente1);
    $grupo->asignarDocente($docente2);

    expect(fn () => $grupo->asignarDocente($docente3))
        ->toThrow(DomainException::class);

    expect($grupo->fresh()->docentes)->toHaveCount(2)
        ->and(DB::table('grupo_docente')->where('docente_cod_empleado', 1003)->exists())->toBeFalse()
        ->and(DB::table('grupo_docente')->count())->toBe(2);
});

test('CP-05 no abre un grupo que tiene catorce estudiantes', function () {
    $grupo = grupoTestCrearGrupoParaApertura(14);

    expect($grupo->puedeAbrirse())->toBeFalse()
        ->and(fn () => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-06 abre un grupo con quince estudiantes y un docente', function () {
    $grupo = grupoTestCrearGrupoParaApertura(15);

    expect($grupo->puedeAbrirse())->toBeTrue();

    $grupo->abrir();

    expect($grupo->fresh()->abierto)->toBeTrue()
        ->and(DB::table('grupos')->where('id', $grupo->id)->value('abierto'))->toBe(1);
});

function grupoTestCrearCurso(): Curso
{
    return Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombre_curso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);
}

function grupoTestCrearDocente(int $codigo, string $nombre, string $apellidos): Docente
{
    return Docente::factory()->create([
        'cod_empleado' => $codigo,
        'nombre_empleado' => $nombre,
        'apellidos_empleado' => $apellidos,
    ]);
}

function grupoTestCrearGrupo(Curso $curso, bool $abierto = true, int $numeroGrupo = 1): Grupo
{
    return Grupo::factory()->for($curso)->create([
        'numero_ciclo' => 2,
        'ano' => 2026,
        'numero_grupo' => $numeroGrupo,
        'abierto' => $abierto,
    ]);
}

function grupoTestCrearGrupoParaApertura(int $cantidadEstudiantes): Grupo
{
    $grupo = grupoTestCrearGrupo(grupoTestCrearCurso(), false);
    $docente = grupoTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiantes = Estudiante::factory()
        ->count($cantidadEstudiantes)
        ->recycle($docente)
        ->create();
    $grupo->asignarDocente($docente);
    $grupo->estudiantes()->attach($estudiantes->modelKeys());

    return $grupo;
}
