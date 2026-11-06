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
        Schema::table('activos_fijos', function (Blueprint $table) {
            $table->string('codigo_antiguo', 50)->nullable()->after('codigo_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activos_fijos', function (Blueprint $table) {
            $table->dropColumn('codigo_antiguo');
        });
    }
};
