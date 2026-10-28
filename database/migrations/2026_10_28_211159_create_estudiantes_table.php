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
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->string('carne')->primary();
            $table->string('nombre');
            $table->string('apellidos');

           
            $table->unsignedInteger('consejero_cod_empleado');
            $table->foreign('consejero_cod_empleado')
                  ->references('cod_empleado')
                  ->on('docentes')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
