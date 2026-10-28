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
         Schema::create('estudiante_matriculados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');

            $table->string('estudiante_carne');
            $table->foreign('estudiante_carne')
                  ->references('carne')
                  ->on('estudiantes')
                  ->onDelete('cascade');

            
            $table->float('nota')->nullable();
            $table->unique(['grupo_id', 'estudiante_carne']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante_matriculados');
    }
};
