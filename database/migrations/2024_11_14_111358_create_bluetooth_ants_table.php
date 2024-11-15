<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBluetoothAntsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bluetooth_ants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la antena o sensor
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade'); // Clave foránea con 'production_lines'
            $table->string('mqtt_topic'); // Tópico MQTT asociado
            $table->string('token')->unique(); // Token único para cada entrada
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('bluetooth_ants');
    }
    
}
