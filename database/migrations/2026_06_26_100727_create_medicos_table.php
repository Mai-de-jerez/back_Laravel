<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')
                  ->constrained('usuarios')
                  ->onDelete('cascade');
            $table->foreignId('id_especialidad')
                  ->constrained('especialidades')
                  ->onDelete('restrict');
            $table->string('numero_colegiado', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_modificacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};