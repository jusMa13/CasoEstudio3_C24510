<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EstudianteMatriculado extends Pivot
{
    protected $table = 'estudiante_matriculados';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'grupo_id',
        'estudiante_carne',
        'nota',
    ];

    protected $casts = [
        'nota' => 'float',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_carne', 'carne');
    }
}