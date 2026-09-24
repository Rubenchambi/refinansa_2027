<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_mapeo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_plantilla', 100)->unique(); // Ej: "Caja Arequipa", "Derrama Magisterial"
            $table->string('cartera', 100)->nullable();
            $table->jsonb('mapeo_config'); // Guarda el JSON del mapeo { "nro_documento": "DNI", "capital_deuda": "CAPITAL", ... }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_mapeo');
    }
};