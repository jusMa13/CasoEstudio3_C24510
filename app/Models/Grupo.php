<?php

namespace App\Models;

use Database\Factories\GrupoFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Grupo extends Model
{
    /** @use HasFactory<GrupoFactory> */
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'curso_id',
        'numero_ciclo',
        'ano',
        'numero_grupo',
        'abierto',
    ];

    protected $casts = [
        'numero_ciclo' => 'integer',
        'ano' => 'integer',
        'numero_grupo' => 'integer',
        'abierto' => 'boolean',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'grupo_docente',
            'grupo_id',
            'docente_cod_empleado',
            'id',
            'cod_empleado'
        );
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(
            Estudiante::class,
            'estudiantes_matriculados',
            'grupo_id',
            'estudiante_id',
            'id',
            'carne'
        )->using(EstudianteMatriculado::class)->withPivot('nota');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class, 'grupo_id');
    }

    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->count() >= 2) {
            throw new DomainException('Un grupo no puede tener más de dos docentes.');
        }

        $this->docentes()->attach($docente->cod_empleado);
    }

    public function puedeAbrirse(): bool
    {
        return $this->docentes()->exists()
            && $this->estudiantes()->count() >= 15;
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException('El grupo requiere al menos un docente y 15 estudiantes para abrirse.');
        }

        $this->update(['abierto' => true]);
    }

    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        if ($nota < 0 || $nota > 10) {
            throw new InvalidArgumentException('La nota debe estar entre 0 y 10.');
        }

        $matricula = $this->matriculas()
            ->where('estudiante_id', $estudiante->carne)
            ->firstOrFail();

        $matricula->update(['nota' => $nota]);
    }
}
