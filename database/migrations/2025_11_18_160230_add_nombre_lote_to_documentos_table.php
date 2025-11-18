<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('documentos', function (Blueprint $table) {
        $table->string('nombre_lote')->nullable()->default('Lote sin nombre')->after('lote_id');
    });
}

public function down()
{
    Schema::table('documentos', function (Blueprint $table) {
        $table->dropColumn('nombre_lote');
    });
}
};
