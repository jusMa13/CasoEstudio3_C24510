<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    protected $table = 'estudiantes';
    protected $primaryKey = 'carne';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'carne',
        'nombre',
        'apellidos',
        'consejero_cod_empleado',
    ];

    /** Profesor consejero (solo uno). */
    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'consejero_cod_empleado', 'cod_empleado');
    }

    /** Grupos en los que está matriculado (con su nota). */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'estudiante_matriculados',
            'estudiante_carne',
            'grupo_id',
            'carne',
            'id'
        )->using(EstudianteMatriculado::class)->withPivot('id', 'nota');
    }

    /** Registros de matrícula. */
    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class, 'estudiante_carne', 'carne');
    }
}