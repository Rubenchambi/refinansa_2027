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
        Schema::table('asignaciones_cartera', function (Blueprint $table) {
            $table->string('periodo', 7)->after('nombre_archivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
        Schema::table('asignaciones_cartera', function (Blueprint $table) {
            $table->dropColumn('periodo');
        });
    }
};
