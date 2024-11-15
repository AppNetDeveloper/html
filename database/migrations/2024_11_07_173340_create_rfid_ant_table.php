<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRfidAntTable extends Migration
{
    public function up()
    {
        Schema::create('rfid_ants', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // Nombre de la antena o sensor
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade'); // Clave foránea con 'production_lines'
            $table->string('mqtt_topic');    // Tópico MQTT asociado
            
            $table->string('token')->unique(); // Token único para cada entrada
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfid_ants');
    }
}
