<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $table = 'docentes';
    protected $primaryKey = 'cod_empleado';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'cod_empleado',
        'nombre_empleado',
        'apellidos_empleado',
    ];

    /** Grupos que imparte el docente. */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'docente_grupo',
            'docente_cod_empleado',
            'grupo_id',
            'cod_empleado',
            'id'
        );
    }

     public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'consejero_cod_empleado', 'cod_empleado');
    }
}