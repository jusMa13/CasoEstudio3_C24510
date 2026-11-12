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
        Schema::table('docente_grupo', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropForeign(['docente_cod_empleado']);
        });

        Schema::table('estudiante_matriculados', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropForeign(['estudiante_carne']);
            $table->dropUnique(['grupo_id', 'estudiante_carne']);
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropForeign(['consejero_cod_empleado']);
        });

        Schema::rename('estudiante_matriculados', 'estudiantes_matriculados');
        Schema::rename('docente_grupo', 'grupo_docente');

        Schema::table('estudiantes_matriculados', function (Blueprint $table) {
            $table->renameColumn('estudiante_carne', 'estudiante_id');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->renameColumn('consejero_cod_empleado', 'consejero_id');
        });

        Schema::table('estudiantes_matriculados', function (Blueprint $table) {
            $table->foreign('grupo_id', 'estudiantes_matriculados_grupo_id_foreign')
                ->references('id')
                ->on('grupos')
                ->cascadeOnDelete();
            $table->foreign('estudiante_id', 'estudiantes_matriculados_estudiante_id_foreign')
                ->references('carne')
                ->on('estudiantes')
                ->cascadeOnDelete();
            $table->unique(['grupo_id', 'estudiante_id'], 'estudiantes_matriculados_grupo_id_estudiante_id_unique');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->foreign('consejero_id', 'estudiantes_consejero_id_foreign')
                ->references('cod_empleado')
                ->on('docentes')
                ->restrictOnDelete();
        });

        Schema::table('grupo_docente', function (Blueprint $table) {
            $table->foreign('grupo_id', 'grupo_docente_grupo_id_foreign')
                ->references('id')
                ->on('grupos')
                ->cascadeOnDelete();
            $table->foreign('docente_cod_empleado', 'grupo_docente_docente_cod_empleado_foreign')
                ->references('cod_empleado')
                ->on('docentes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupo_docente', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropForeign(['docente_cod_empleado']);
        });

        Schema::table('estudiantes_matriculados', function (Blueprint $table) {
            $table->dropForeign(['grupo_id']);
            $table->dropForeign(['estudiante_id']);
            $table->dropUnique('estudiantes_matriculados_grupo_id_estudiante_id_unique');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropForeign(['consejero_id']);
        });

        Schema::table('estudiantes_matriculados', function (Blueprint $table) {
            $table->renameColumn('estudiante_id', 'estudiante_carne');
        });

        Schema::rename('estudiantes_matriculados', 'estudiante_matriculados');
        Schema::rename('grupo_docente', 'docente_grupo');

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->renameColumn('consejero_id', 'consejero_cod_empleado');
        });

        Schema::table('estudiante_matriculados', function (Blueprint $table) {
            $table->foreign('grupo_id', 'estudiante_matriculados_grupo_id_foreign')
                ->references('id')
                ->on('grupos')
                ->cascadeOnDelete();
            $table->foreign('estudiante_carne', 'estudiante_matriculados_estudiante_carne_foreign')
                ->references('carne')
                ->on('estudiantes')
                ->cascadeOnDelete();
            $table->unique(['grupo_id', 'estudiante_carne'], 'estudiante_matriculados_grupo_id_estudiante_carne_unique');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->foreign('consejero_cod_empleado', 'estudiantes_consejero_cod_empleado_foreign')
                ->references('cod_empleado')
                ->on('docentes')
                ->restrictOnDelete();
        });

        Schema::table('docente_grupo', function (Blueprint $table) {
            $table->foreign('grupo_id', 'docente_grupo_grupo_id_foreign')
                ->references('id')
                ->on('grupos')
                ->cascadeOnDelete();
            $table->foreign('docente_cod_empleado', 'docente_grupo_docente_cod_empleado_foreign')
                ->references('cod_empleado')
                ->on('docentes')
                ->cascadeOnDelete();
        });
    }
};
