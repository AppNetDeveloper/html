<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBarcodeIdToModbusesTable extends Migration
{
    public function up()
    {
        Schema::table('modbuses', function (Blueprint $table) {
            $table->unsignedBigInteger('barcoder_id')->nullable()->after('id');
            
            // Definir la relación foránea con la tabla `barcodes`
            $table->foreign('barcoder_id')->references('id')->on('barcodes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('modbuses', function (Blueprint $table) {
            // Primero eliminar la clave foránea antes de eliminar la columna
            $table->dropForeign(['barcoder_id']);
            $table->dropColumn('barcoder_id');
        });
    }
}

