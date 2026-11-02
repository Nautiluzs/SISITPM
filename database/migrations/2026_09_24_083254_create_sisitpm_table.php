<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ============================================================
    // ORDEN DE CREACIÓN (por dependencias):
    //   1. carreras
    //   2. usuarios (FK a carreras)
    //   3. ambientes (FK a carreras)
    //   4. ambiente_user (FK a ambientes y usuarios)
    //   5. categorias (FK a ambientes)
    //   6. fuentes_financiamiento
    //   7. items (FK a carreras y ambientes)
    //   8. item_usuario (FK a items y usuarios)
    //   9. activos_fijos (FK a todo + imágenes adicionales)
    //  10. actas_baja
    //  11. actas_transferencia
    //  12. acta_transferencia_activo (pivot acta ↔ activo)
    //  13. verificaciones_fisicas
    //  14. historial_titulares
    //  15. historial_reportes (FK a usuarios)
    // ============================================================
    public function up(): void
    {
        // =====================================================
        // 1. CARRERAS
        // =====================================================
        Schema::create('carreras', function (Blueprint $table) {
            $table->id('id_carrera');
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->timestamps();

            $table->index('nombre', 'idx_carreras_nombre');
        });

        // =====================================================
        // 2. USUARIOS
        // =====================================================
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre_completo', 150);
            $table->string('ci', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('cargo', 100)->nullable();
            $table->string('unidad', 100)->nullable();
            $table->string('password');
            $table->string('rol', 50)->default('DOCENTE_CUSTODIO');
            $table->foreignId('id_carrera')
                ->nullable()
                ->constrained('carreras', 'id_carrera')
                ->nullOnDelete();
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->boolean('puede_verificar')->default(false);

            $table->rememberToken();
            $table->timestamps();

            $table->index(['rol', 'estado'], 'idx_usuarios_rol_estado');
            $table->index('id_carrera', 'idx_usuarios_carrera');
        });

        // =====================================================
        // 3. AMBIENTES
        // =====================================================
        Schema::create('ambientes', function (Blueprint $table) {
            $table->id('id_ambiente');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('bloque', 20)->nullable();
            $table->string('piso', 10)->nullable();
            $table->foreignId('id_carrera')
                ->constrained('carreras', 'id_carrera')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index('nombre', 'idx_ambientes_nombre');
            $table->index('id_carrera', 'idx_ambientes_carrera');
        });

        // =====================================================
        // 4. AMBIENTE_USER (Pivot ambientes ↔ usuarios)
        // =====================================================
        Schema::create('ambiente_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ambiente')
                ->constrained('ambientes', 'id_ambiente')
                ->cascadeOnDelete();
            $table->foreignId('id_usuario')
                ->constrained('usuarios', 'id_usuario')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_ambiente', 'id_usuario'], 'uq_ambiente_usuario');
        });

        // =====================================================
        // 5. CATEGORÍAS
        // =====================================================
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->foreignId('id_ambiente')
                ->nullable()
                ->constrained('ambientes', 'id_ambiente')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('nombre', 'idx_categorias_nombre');
            $table->index('id_ambiente', 'idx_categorias_ambiente');
        });

        // =====================================================
        // 6. FUENTES DE FINANCIAMIENTO
        // =====================================================
        Schema::create('fuentes_financiamiento', function (Blueprint $table) {
            $table->id('id_fuente');
            $table->string('nombre', 100)->unique();
            $table->string('codigo', 10)->nullable();
            $table->timestamps();

            $table->index('codigo', 'idx_fuentes_codigo');
        });

        // =====================================================
        // 7. ITEMS (plazas institucionales)
        // =====================================================
        Schema::create('items', function (Blueprint $table) {
            $table->id('id_item');
            $table->string('numero_item', 20)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->foreignId('id_carrera')
                ->nullable()
                ->constrained('carreras', 'id_carrera')
                ->nullOnDelete();
            $table->foreignId('id_ambiente')
                ->nullable()
                ->constrained('ambientes', 'id_ambiente')
                ->nullOnDelete();
            $table->string('unidad', 100)->nullable();
            $table->enum('estado', ['VACANTE', 'OCUPADO'])->default('VACANTE');
            $table->date('fecha_vacancia')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'id_carrera'], 'idx_items_estado_carrera');
            $table->index('id_ambiente', 'idx_items_ambiente');
        });

        // =====================================================
        // 8. ITEM_USUARIO (pivot: ítem ↔ custodios)
        // =====================================================
        Schema::create('item_usuario', function (Blueprint $table) {
            $table->id('id_item_usuario');
            $table->foreignId('id_item')
                ->constrained('items', 'id_item')
                ->cascadeOnDelete();
            $table->foreignId('id_usuario')
                ->constrained('usuarios', 'id_usuario')
                ->cascadeOnDelete();
            $table->enum('tipo', ['TITULAR', 'SECUNDARIO', 'RESPONSABLE'])->default('TITULAR');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->unique(['id_item', 'id_usuario'], 'uq_item_usuario');
            $table->index('id_usuario', 'idx_item_usuario_user');
            $table->index('fecha_fin', 'idx_item_usuario_fin');
        });

        // =====================================================
        // 9. ACTIVOS FIJOS
        // =====================================================
        Schema::create('activos_fijos', function (Blueprint $table) {
            $table->id('id_activo');

            // Identificación
            $table->string('codigo_activo', 50)->unique();
            $table->enum('tipo_bien', ['ACTIVO_FIJO', 'NO_ACTIVO', 'RV'])
                ->default('ACTIVO_FIJO');
            $table->string('nombre', 150);

            // ✅ VISAF (SÍ/NO) - reemplaza al antiguo enum 'rv'
            $table->boolean('es_visaf')->default(false)
                ->comment('¿El activo es Visaf? SÍ/NO');

            // ✅ REGISTRO VISAF (SÍ/NO) - campo independiente del formulario
            $table->boolean('tiene_registro_visaf')->default(false)
                ->comment('¿Tiene registro de Visaf? SÍ/NO');

            // Descripción
            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();

            // Especificaciones
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 80)->nullable();
            $table->string('numero_serie', 80)->nullable();

            // Valores
            $table->decimal('valor_adquisicion', 12, 2)->default(0.00);
            $table->date('fecha_adquisicion')->nullable();

            // Estados
            $table->enum('estado_fisico', ['B', 'R', 'M', 'FF'])->default('B');
            $table->enum('estado_registro', ['ACTIVO', 'ASIGNADO', 'EN_TRANSFERENCIA', 'BAJA'])
                ->default('ACTIVO');

            // Archivos e Imágenes adicionales
            $table->string('imagen')->nullable();
            $table->string('imagen_lateral')->nullable();
            $table->string('imagen_detalle')->nullable();
            $table->string('ruta_qr')->nullable();
            $table->string('codigo_qr')->nullable();

            // Relaciones
            $table->foreignId('id_categoria')
                ->constrained('categorias', 'id_categoria');
            $table->foreignId('id_ambiente')
                ->constrained('ambientes', 'id_ambiente');
            $table->foreignId('id_fuente')
                ->constrained('fuentes_financiamiento', 'id_fuente');
            $table->foreignId('id_custodio')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->foreignId('id_item')
                ->nullable()
                ->constrained('items', 'id_item')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('nombre', 'idx_activos_nombre');
            $table->index('tipo_bien', 'idx_activos_tipo');
            $table->index('es_visaf', 'idx_activos_visaf');
            $table->index('tiene_registro_visaf', 'idx_activos_registro_visaf');
            $table->index('estado_fisico', 'idx_activos_ef');
            $table->index('estado_registro', 'idx_activos_er');
            $table->index('fecha_adquisicion', 'idx_activos_fecha');
            $table->index(['id_categoria', 'nombre'], 'idx_activos_cat_nom');
            $table->index(['id_item', 'estado_registro'], 'idx_activos_item_er');
            $table->index(['id_ambiente', 'tipo_bien'], 'idx_activos_amb_tipo');
        });

        // =====================================================
        // 10. ACTAS DE BAJA
        // =====================================================
        Schema::create('actas_baja', function (Blueprint $table) {
            $table->id('id_acta_baja');
            $table->string('numero_acta', 50)->unique();
            $table->foreignId('id_activo')
                ->constrained('activos_fijos', 'id_activo');
            $table->foreignId('id_usuario')
                ->constrained('usuarios', 'id_usuario');
            $table->text('motivo');
            $table->string('causal_baja')->nullable();
            $table->date('fecha_baja');
            $table->string('archivo_resolucion')->nullable();

            // Evidencias fotográficas
            $table->string('foto_evidencia')->nullable();
            $table->string('foto_evidencia_1')->nullable();
            $table->string('foto_evidencia_2')->nullable();
            $table->string('foto_evidencia_3')->nullable();

            // Responsable del acta
            $table->string('responsable_nombre')->nullable();
            $table->string('responsable_ci')->nullable();

            // Flujo de aprobación
            $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])
                ->default('PENDIENTE');
            $table->foreignId('id_aprobador')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('comentario_aprobacion')->nullable();

            // Sustitución
            $table->boolean('es_sustitucion')->default(false);
            $table->json('datos_sustituto')->nullable();
            $table->foreignId('id_activo_sustituto')
                ->nullable()
                ->constrained('activos_fijos', 'id_activo')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'id_acta_baja'], 'idx_baja_estado');
            $table->index('id_usuario', 'idx_baja_user');
        });

        // =====================================================
        // 11. ACTAS DE TRANSFERENCIA
        // =====================================================
        Schema::create('actas_transferencia', function (Blueprint $table) {
            $table->id('id_acta_transferencia');
            $table->string('numero_acta', 50)->unique();
            $table->foreignId('id_activo')
                ->constrained('activos_fijos', 'id_activo');
            $table->foreignId('id_origen_ambiente')
                ->constrained('ambientes', 'id_ambiente');
            $table->foreignId('id_destino_ambiente')
                ->constrained('ambientes', 'id_ambiente');
            $table->foreignId('id_custodio_anterior')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->foreignId('id_custodio_nuevo')
                ->constrained('usuarios', 'id_usuario');
            $table->text('observaciones')->nullable();
            $table->date('fecha_transferencia');

            // Flujo de aprobación
            $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])
                ->default('PENDIENTE');
            $table->foreignId('id_aprobador')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('comentario_aprobacion')->nullable();

            // Estado previo para restaurar si se rechaza
            $table->string('estado_anterior', 25)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'id_acta_transferencia'], 'idx_transf_estado');
        });

        // =====================================================
        // 12. ACTA_TRANSFERENCIA_ACTIVO (Pivot acta ↔ activo)
        // =====================================================
        Schema::create('acta_transferencia_activo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_acta_transferencia')
                ->constrained('actas_transferencia', 'id_acta_transferencia')
                ->cascadeOnDelete();
            $table->foreignId('id_activo')
                ->constrained('activos_fijos', 'id_activo')
                ->cascadeOnDelete();
            $table->integer('cantidad')->default(1);
            $table->timestamps();

            $table->unique(['id_acta_transferencia', 'id_activo'], 'uq_acta_activo');
        });

        // =====================================================
        // 13. VERIFICACIONES FÍSICAS
        // =====================================================
        Schema::create('verificaciones_fisicas', function (Blueprint $table) {
            $table->id('id_verificacion');
            $table->foreignId('id_activo')
                ->constrained('activos_fijos', 'id_activo');
            $table->foreignId('id_ambiente_escaneo')
                ->constrained('ambientes', 'id_ambiente');
            $table->foreignId('id_verificador')
                ->constrained('usuarios', 'id_usuario');
            $table->dateTime('fecha_verificacion');
            $table->boolean('es_correspondencia_correcta')->default(true);
            $table->boolean('sincronizado_offline')->default(false);
            $table->string('dispositivo_uuid')->nullable();
            $table->json('datos_extra')->nullable();
            $table->enum('estado_fisico_reportado', ['B', 'R', 'M', 'FF'])->nullable();
            $table->text('observaciones')->nullable();

            // Aprobación
            $table->enum('estado_aprobacion',
                ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CORREGIDA']
            )->default('PENDIENTE');
            $table->foreignId('id_aprobador')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('comentario_aprobacion')->nullable();
            $table->boolean('cambio_estado_fisico_solicitado')->default(false);
            $table->string('estado_fisico_solicitado', 2)->nullable();
            $table->boolean('solicitar_baja')->default(false);
            $table->boolean('verificacion_finalizada')->default(false);

            $table->timestamps();

            $table->index(['estado_aprobacion', 'fecha_verificacion'], 'idx_verif_estado_fecha');
            $table->index('id_ambiente_escaneo', 'idx_verif_ambiente');
            $table->index('id_verificador', 'idx_verif_user');
            $table->index('verificacion_finalizada', 'idx_verif_finalizada');
        });

        // =====================================================
        // 14. HISTORIAL DE TITULARES
        // =====================================================
        Schema::create('historial_titulares', function (Blueprint $table) {
            $table->id('id_historial');
            $table->foreignId('id_item')
                ->constrained('items', 'id_item')
                ->cascadeOnDelete();
            $table->foreignId('id_usuario_anterior')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->foreignId('id_usuario_nuevo')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->string('nombre_anterior', 150)->nullable();
            $table->string('nombre_nuevo', 150)->nullable();
            $table->enum('tipo', [
                'CREACION',
                'ASIGNACION',
                'DETACH',
                'SUSTITUCION',
                'RENUNCIA',
            ]);
            $table->date('fecha_evento');
            $table->text('motivo')->nullable();
            $table->foreignId('id_usuario_registro')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['id_item', 'fecha_evento'], 'idx_hist_item_fecha');
        });

        // =====================================================
        // 15. HISTORIAL DE REPORTES
        // =====================================================
        Schema::create('historial_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')
                ->nullable()
                ->constrained('usuarios', 'id_usuario')
                ->nullOnDelete();
            $table->string('tipo_reporte', 100);
            $table->string('formato', 10);
            $table->string('nombre_archivo', 255);
            $table->json('filtros')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'formato']);
            $table->index('tipo_reporte');
        });

        // =====================================================
        // DATOS: Normalización de datos existentes
        // =====================================================
        if (Schema::hasTable('usuarios')) {
            DB::table('usuarios')->update([
                'nombre_completo' => DB::raw('UPPER(nombre_completo)'),
            ]);
        }

        if (Schema::hasTable('carreras')) {
            DB::table('carreras')
                ->where('codigo', 'AGP')
                ->update(['codigo' => 'AGRO']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_reportes');
        Schema::dropIfExists('historial_titulares');
        Schema::dropIfExists('verificaciones_fisicas');
        Schema::dropIfExists('acta_transferencia_activo');
        Schema::dropIfExists('actas_transferencia');
        Schema::dropIfExists('actas_baja');
        Schema::dropIfExists('activos_fijos');
        Schema::dropIfExists('item_usuario');
        Schema::dropIfExists('items');
        Schema::dropIfExists('fuentes_financiamiento');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('ambiente_user');
        Schema::dropIfExists('ambientes');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('carreras');
    }
};
