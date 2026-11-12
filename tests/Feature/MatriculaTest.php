<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;

mutates(Grupo::class);

test('CP-07 no abre un grupo sin docentes aunque tenga veinte estudiantes', function () {
    $grupo = matriculaTestCrearGrupo();
    $consejero = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiantes = Estudiante::factory()
        ->count(20)
        ->recycle($consejero)
        ->create();

    $grupo->estudiantes()->attach($estudiantes->modelKeys());

    expect($grupo->puedeAbrirse())->toBeFalse()
        ->and(fn () => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-08 asocia al estudiante con su único docente consejero', function () {
    $docente = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiante = matriculaTestCrearEstudiante($docente);

    expect($estudiante->consejero->cod_empleado)->toBe(1001)
        ->and($docente->fresh()->estudiantes)->toHaveCount(1)
        ->and($docente->fresh()->estudiantes->first()->carne)->toBe('C12345')
        ->and($estudiante->fresh()->consejero_id)->toBe(1001);
});

test('CP-09 cambiar de consejero reemplaza al anterior', function () {
    $docente1 = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $docente2 = matriculaTestCrearDocente(1002, 'Luis', 'Rojas Solano');
    $estudiante = matriculaTestCrearEstudiante($docente1);

    $estudiante->consejero()->associate($docente2);
    $estudiante->save();

    expect($estudiante->fresh()->consejero->cod_empleado)->toBe(1002)
        ->and($docente1->fresh()->estudiantes)->toBeEmpty()
        ->and($docente2->fresh()->estudiantes)->toHaveCount(1)
        ->and($docente2->fresh()->estudiantes->first()->carne)->toBe('C12345');
});

test('CP-10 matricular a un estudiante deja la nota vacía en el pivote', function () {
    $grupo = matriculaTestCrearGrupo();
    $docente = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiante = matriculaTestCrearEstudiante($docente);

    $grupo->estudiantes()->attach($estudiante->carne);

    $matricula = $grupo->fresh()->estudiantes->first()->pivot;

    expect($matricula)->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($matricula->nota)->toBeNull()
        ->and($grupo->matriculas()->count())->toBe(1)
        ->and($grupo->matriculas()->first()->estudiante_id)->toBe('C12345');
});

test('CP-11 guarda la nota final como un valor decimal', function () {
    $grupo = matriculaTestCrearGrupo();
    $docente = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiante = matriculaTestCrearEstudiante($docente);
    $otroEstudiante = Estudiante::factory()
        ->recycle($docente)
        ->create(['carne' => 'C67890']);
    $grupo->estudiantes()->attach($estudiante->carne);
    $grupo->estudiantes()->attach($otroEstudiante->carne);

    $grupo->registrarNota($estudiante, 8.5);

    $matricula = $grupo->matriculas()->where('estudiante_id', 'C12345')->firstOrFail();

    expect($matricula->nota)->toBe(8.5)
        ->and($matricula->nota)->toBeFloat()
        ->and($matricula->grupo_id)->toBe($grupo->id)
        ->and($matricula->estudiante_id)->toBe('C12345')
        ->and($grupo->matriculas()->where('estudiante_id', 'C67890')->value('nota'))->toBeNull();
});

test('CP-12 rechaza notas fuera del rango de cero a diez', function (float $nota) {
    $grupo = matriculaTestCrearGrupo();
    $docente = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiante = matriculaTestCrearEstudiante($docente);
    $grupo->estudiantes()->attach($estudiante->carne);

    expect(fn () => $grupo->registrarNota($estudiante, $nota))
        ->toThrow(InvalidArgumentException::class);

    expect($grupo->matriculas()->where('estudiante_id', 'C12345')->value('nota'))->toBeNull();
})->with([
    'menor que cero' => -1.0,
    'mayor que diez' => 10.5,
]);

test('CP-13 impide matricular dos veces al mismo estudiante en un grupo', function () {
    $grupo = matriculaTestCrearGrupo();
    $docente = matriculaTestCrearDocente(1001, 'Ana', 'Mora Vargas');
    $estudiante = matriculaTestCrearEstudiante($docente);
    $grupo->estudiantes()->attach($estudiante->carne);

    expect(fn () => $grupo->estudiantes()->attach($estudiante->carne))
        ->toThrow(QueryException::class);

    expect($grupo->matriculas()->where('estudiante_id', 'C12345')->count())->toBe(1);
});

function matriculaTestCrearGrupo(): Grupo
{
    $curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombre_curso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    return Grupo::factory()->for($curso)->create([
        'numero_ciclo' => 2,
        'ano' => 2026,
        'numero_grupo' => 1,
        'abierto' => false,
    ]);
}

function matriculaTestCrearDocente(int $codigo, string $nombre, string $apellidos): Docente
{
    return Docente::factory()->create([
        'cod_empleado' => $codigo,
        'nombre_empleado' => $nombre,
        'apellidos_empleado' => $apellidos,
    ]);
}

function matriculaTestCrearEstudiante(Docente $consejero): Estudiante
{
    return Estudiante::factory()
        ->recycle($consejero)
        ->create([
            'carne' => 'C12345',
            'nombre' => 'María',
            'apellidos' => 'Solís Quesada',
        ]);
}
