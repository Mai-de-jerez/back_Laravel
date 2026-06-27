<?php

use App\Enums\DiaSemana;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_medico')
                  ->constrained('medicos')
                  ->onDelete('cascade');
            $table->enum('dia_semana', array_column(DiaSemana::cases(), 'value'));
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_modificacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
