<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBluetoothReadingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bluetooth_readings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre o ubicación del dispositivo Bluetooth
            $table->string('token')->unique(); // Token único para el dispositivo o lectura
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade'); // Relación con línea de producción
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('bluetooth_readings');
    }
    
}
