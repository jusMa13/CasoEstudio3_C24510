<?php

namespace App\Models;

use Database\Factories\EstudianteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    /** @use HasFactory<EstudianteFactory> */
    use HasFactory;

    protected $table = 'estudiantes';

    protected $primaryKey = 'carne';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'carne',
        'nombre',
        'apellidos',
        'consejero_id',
    ];

    /** Profesor consejero (solo uno). */
    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'consejero_id', 'cod_empleado');
    }

    /** Grupos en los que está matriculado (con su nota). */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'estudiantes_matriculados',
            'estudiante_id',
            'grupo_id',
            'carne',
            'id'
        )->using(EstudianteMatriculado::class)->withPivot('nota');
    }

    /** Registros de matrícula. */
    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class, 'estudiante_id', 'carne');
    }
}
