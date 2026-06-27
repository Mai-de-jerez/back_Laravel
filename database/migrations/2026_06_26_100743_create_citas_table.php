<?php

use App\Enums\EstadoCita;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')
                  ->constrained('usuarios')
                  ->onDelete('cascade');
            $table->foreignId('id_medico')
                  ->constrained('medicos')
                  ->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora');
            $table->enum('estado', array_column(EstadoCita::cases(), 'value'))
                  ->default('pendiente');
            $table->text('motivo')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_modificacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};