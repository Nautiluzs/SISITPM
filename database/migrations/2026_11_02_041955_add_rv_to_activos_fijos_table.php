<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('activos_fijos', 'rv')) {
            Schema::table('activos_fijos', function (Blueprint $table) {
                $table->enum('rv', ['NO', 'SI'])
                    ->default('NO')
                    ->after('tipo_bien');
            });
        }

        if (Schema::hasColumn('activos_fijos', 'es_visaf')) {
            DB::table('activos_fijos')
                ->where('es_visaf', true)
                ->update(['rv' => 'SI']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('activos_fijos', 'rv')) {
            Schema::table('activos_fijos', function (Blueprint $table) {
                $table->dropColumn('rv');
            });
        }
    }
};
