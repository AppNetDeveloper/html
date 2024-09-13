<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInversSensorsToSensorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->boolean('invers_sensors')->default(false); // Nuevo campo invers_sensors, tipo booleano
        });
    }

    public function down()
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn('invers_sensors'); // Elimina el campo en caso de rollback
        });
    }

}
