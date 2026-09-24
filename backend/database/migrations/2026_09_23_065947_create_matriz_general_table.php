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
        // 1. Eliminación preventiva para asegurar instalación limpia
        Schema::dropIfExists('matriz_general');

        // 2. Creación con los 82 campos oficiales de la Datamart
        Schema::create('matriz_general', function (Blueprint $table) {
            $table->id();

            // 1. IDENTIFICACIÓN Y DATOS DEL CLIENTE
            $table->string('idcliente', 100)->index(); // DNI + Cuenta / Operación
            $table->string('cuenta_cod_credito', 50)->nullable()->index();
            $table->string('operacion_cod_modular', 50)->nullable()->index();
            $table->string('nro_documento', 20)->nullable()->index();
            $table->string('unico', 10)->nullable();
            $table->decimal('monto_unificado', 18, 2)->default(0.00);
            $table->string('nombre_cliente', 100)->nullable();

            // 2. CARTERA Y NEGOCIO
            $table->string('cartera', 50)->nullable();
            $table->string('sub_cartera', 50)->nullable();
            $table->string('region', 50)->nullable();
            $table->string('linea_negocio', 50)->nullable();
            $table->string('año_credito_castigo', 6)->nullable();
            $table->string('mes', 5)->nullable();
            $table->string('moneda', 10)->nullable();

            // 3. DEUDAS Y CAMPAÑAS
            $table->decimal('capital_deuda', 18, 2)->default(0.00);
            $table->string('rango_deuda', 20)->nullable();
            $table->decimal('total_saldo_vencido', 18, 2)->default(0.00);
            $table->decimal('total_saldo_diferido', 18, 2)->default(0.00);
            $table->string('desc_campaña_cancelacion', 100)->nullable();
            $table->decimal('monto_campaña_cancelacion', 18, 2)->default(0.00);
            $table->decimal('monto_mix_cancelacion', 18, 2)->default(0.00);
            $table->string('rango_campaña_canc', 50)->nullable();
            $table->string('desc_campaña_cancelacion_supervisor', 100)->nullable();
            $table->decimal('monto_campaña_cancelacion_supervisor', 18, 2)->default(0.00);
            $table->decimal('monto_mixto_super', 18, 2)->default(0.00);
            $table->string('rango_campaña_super', 50)->nullable();

            // 4. HISTÓRICOS Y MEMORIA (PUENTE ENTRE MESES)
            $table->string('pdp_histo', 20)->nullable();
            $table->string('cel3', 15)->nullable();
            $table->string('historico_contac', 10)->nullable();
            $table->string('cel2', 15)->nullable();
            $table->string('grupo_gest_mes_anterior', 50)->nullable();
            $table->string('resultado_gest_mes_anterior', 50)->nullable();
            $table->string('telef_mes_anterior', 15)->nullable();

            // 5. GESTIÓN CALL CENTER (MES ACTUAL)
            $table->string('grupo_gestactual_homologado', 50)->nullable();
            $table->string('mes_actual', 20)->nullable();
            $table->string('resultado_actual', 50)->nullable();
            $table->string('justificacion_actual', 50)->nullable();
            $table->string('cel1', 15)->nullable();
            $table->string('fecha_gest', 20)->nullable();
            $table->string('asesor', 50)->nullable();
            $table->integer('cantidad_gest')->nullable()->default(0);
            $table->string('ultima_gest', 50)->nullable();
            $table->integer('frecuencia')->nullable();
            $table->string('rango_frecuencia', 50)->nullable();
            $table->string('predic_mes_actual', 100)->nullable();
            $table->integer('predic_cantidad_gest')->nullable()->default(0);

            // 6. PDP, PAGOS Y COBERTURAS CALL
            $table->string('contador_pdp', 10)->nullable();
            $table->string('fecha_pdp', 20)->nullable();
            $table->decimal('mto_pdp', 18, 2)->default(0.00);
            $table->string('estado_pdp', 20)->nullable();
            $table->decimal('pagos', 18, 2)->default(0.00);
            $table->string('fecha_pago', 20)->nullable();
            $table->string('cumplimiento', 20)->nullable();

            // 7. CANALES MASIVOS Y COBERTURAS
            $table->string('envio_ivr', 20)->nullable();
            $table->string('envio_sms', 20)->nullable();
            $table->string('envio_correo', 20)->nullable();
            $table->string('envio_wsp', 20)->nullable();
            $table->string('cobertura_masivos', 50)->nullable();
            $table->string('cobertura_call', 50)->nullable();
            $table->string('cobertura_predic', 50)->nullable();

            // 8. ASIGNACIÓN, FOCOS Y EXPEDIENTES
            $table->string('asig_mes', 50)->nullable();
            $table->string('continuidad', 50)->nullable();
            $table->string('altos', 20)->nullable();
            $table->string('focos', 50)->nullable();
            $table->string('convenios', 20)->nullable();
            $table->string('backoffice', 50)->nullable();
            $table->string('mixtos', 50)->nullable();
            $table->string('estado_expedientes', 150)->nullable();
            $table->string('calificacion_expedientes', 120)->nullable();

            // 9. COMODINES Y VARIABLES DINÁMICAS
            $table->string('p1', 100)->nullable();
            $table->string('p2', 100)->nullable();
            $table->string('p3', 100)->nullable();
            $table->string('p4', 100)->nullable();
            $table->string('p5', 100)->nullable();
            $table->string('p6', 100)->nullable();
            $table->string('var1', 200)->nullable();
            $table->string('var2', 200)->nullable();
            $table->string('var3', 200)->nullable();
            $table->string('var4', 200)->nullable();
            $table->string('var5', 200)->nullable();
            $table->string('var6', 200)->nullable();
            $table->string('var7', 200)->nullable();
            $table->string('var8', 200)->nullable();
            $table->string('var9', 200)->nullable();
            $table->string('score', 20)->nullable();

            // 10. UBICACIÓN Y GEOGRAFÍA (ORIGEN Y SEARCH)
            $table->string('departamento_origen', 200)->nullable();
            $table->string('provincia_origen', 200)->nullable();
            $table->string('distrito_origen', 200)->nullable();
            $table->string('direccion_origen', 255)->nullable();
            $table->string('ubigeo_origen', 10)->nullable();
            $table->string('departamento_search', 100)->nullable();
            $table->string('provincia_search', 100)->nullable();
            $table->string('distrito_search', 200)->nullable();
            $table->string('direccion_search', 255)->nullable();
            $table->char('ubigeo_search', 10)->nullable();

            // 11. GESTIÓN DE CAMPO
            $table->string('grupo_gest_homologado_campo', 50)->nullable();
            $table->string('grupo_gest_campo', 255)->nullable();
            $table->string('resultado_gest_campo', 255)->nullable();
            $table->string('fecha_gest_campo', 20)->nullable();
            $table->string('gestor_campo', 50)->nullable();
            $table->string('fecha_pdp_campo', 20)->nullable();
            $table->decimal('mto_pdp_campo', 18, 2)->default(0.00);
            $table->integer('cantidad_gest_campo')->nullable()->default(0);
            $table->string('cobertura_campo', 50)->nullable();
            $table->string('contac_dual', 50)->nullable();
            $table->string('motivo', 20)->nullable();
            $table->string('coberturable', 50)->nullable();
            $table->string('tipo_gestion', 50)->nullable(); // 'GEST CALL' o 'GEST CAMPO'

            // 12. CONTROL DE PERIODO Y OPERACIÓN
            $table->integer('dia_asignacion')->nullable();
            $table->integer('mes_asignacion')->index();
            $table->integer('año_asignacion')->index();
            $table->string('tipo_carga', 20)->default('REAL');
            $table->string('estado_cierre', 20)->default('ABIERTO');
            $table->string('estado', 20)->default('Activo');
            $table->string('telefono_score', 20)->nullable();
            $table->integer('peso_score')->nullable();
            $table->string('capacity', 50)->nullable();

            $table->timestamps();

            // Restricción única compuesta
            $table->unique(['idcliente', 'mes_asignacion', 'año_asignacion'], 'uk_cliente_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriz_general');
    }
};