<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('docente_grupo', function (Blueprint $table) {
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');

            $table->unsignedInteger('docente_cod_empleado');
            $table->foreign('docente_cod_empleado')
                  ->references('cod_empleado')
                  ->on('docentes')
                  ->onDelete('cascade');

            $table->primary(['grupo_id', 'docente_cod_empleado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docente_grupo');
    }
};
