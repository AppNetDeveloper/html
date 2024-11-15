<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRfidReadingsTable extends Migration
{
    public function up()
    {
        Schema::create('rfid_readings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre o ubicación del dispositivo RFID
            $table->string('epc')->unique(); // EPC - Identificador único del grupo RFID
            $table->string('token')->unique(); // Token único para el dispositivo o lectura
            
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade'); // Relación con línea de producción
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfid_readings');
    }
}
