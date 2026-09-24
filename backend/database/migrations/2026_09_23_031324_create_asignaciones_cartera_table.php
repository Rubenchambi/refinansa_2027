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
        Schema::create('asignaciones_cartera', function (Blueprint $table) {
            $table->id();
            $table->string('lote_cartera');    // Identificador único de la carga (Ej: BCP_AGO_2026)
            $table->string('nombre_archivo');  // Nombre original del archivo subido
            $table->longText('datos_fila');    // Fila original almacenada en JSON (espejo fiel 100% dinámico)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_cartera');
    }
};
