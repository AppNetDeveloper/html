<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIniciarModelToBarcodesTable extends Migration
{
    public function up()
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->string('iniciar_model')->default('INICIAR')->after('conexion_type');
        });
    }

    public function down()
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropColumn('iniciar_model');
        });
    }
}

