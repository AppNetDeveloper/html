<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScadaMaterialTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scada_material_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scada_id')->constrained('scada')->onDelete('cascade');
            $table->string('name');
            $table->string('density');  // Campo de densidad en inglés
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
        Schema::dropIfExists('scada_material_type');
    }
}
