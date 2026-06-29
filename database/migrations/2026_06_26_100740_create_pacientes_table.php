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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            
            // Relación limpia con tu tabla usuarios
            $table->foreignId('id_usuario')
                  ->constrained('usuarios')
                  ->onDelete('cascade');
            
            // Campos específicos del paciente
            $table->string('numero_tarjeta');
            $table->string('compania');
            
            // Tus nombres personalizados para el control de tiempo
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_modificacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
