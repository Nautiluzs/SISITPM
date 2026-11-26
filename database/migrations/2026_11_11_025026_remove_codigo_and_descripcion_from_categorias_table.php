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
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique('categorias_codigo_unique');
            $table->dropColumn(['codigo', 'descripcion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->unique('categorias_codigo_unique');
            $table->string('descripcion', 255)->nullable();
        });
    }
};
