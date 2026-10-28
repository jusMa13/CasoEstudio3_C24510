<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
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
        'ano'          => 'integer',
        'numero_grupo' => 'integer',
        'abierto'      => 'boolean',
    ];

   
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

   
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'docente_grupo',
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
            'estudiante_matriculados',
            'grupo_id',
            'estudiante_carne',
            'id',
            'carne'
        )->using(EstudianteMatriculado::class)->withPivot('id', 'nota');
    }

   
    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class, 'grupo_id');
    }
}