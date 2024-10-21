<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScadaListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scada_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scada_id')->constrained('scada')->onDelete('cascade');
            $table->foreignId('modbus_id')->constrained('modbuses')->onDelete('cascade');
            $table->string('fillinglevels');  // Campo string porque no solo contendrá números
            $table->foreignId('material_type_id')->constrained('scada_material_type')->onDelete('cascade'); // Referencia a scada_material_type
            $table->string('m3')->nullable();  // Añadir campo m3 como strin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scada_list');
    }
}
