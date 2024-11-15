<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRfidDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('rfid_details', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Nombre descriptivo o ubicación
            $table->string('token')->unique(); // Token único para cada entrada
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('rfid_reading_id')->constrained('rfid_readings')->onDelete('cascade'); // Relación con `rfid_readings`
            $table->integer('rfid_type')->default(0); // Tipo de RFID
            $table->integer('count_total')->default(0); // Contador total de lecturas
            $table->integer('count_total_0')->default(0); // Contador total de lecturas inactivas
            $table->integer('count_total_1')->default(0); // Contador total de lecturas activas
            $table->integer('count_shift_0')->default(0); // Contador de lecturas inactivas por turno
            $table->integer('count_shift_1')->default(0); // Contador de lecturas activas por turno
            $table->integer('count_order_0')->default(0); // Contador de lecturas inactivas por orden
            $table->integer('count_order_1')->default(0); // Contador de lecturas activas por orden
            $table->string('mqtt_topic_1');            // Otro tópico MQTT
            $table->string('function_model_0');        // Función del modelo 0
            $table->string('function_model_1');        // Función del modelo 1
            $table->boolean('invers_sensors')->default(false); // Indicador de inversión de sensores
            $table->string('unic_code_order')->nullable();    // Código único de la orden
            $table->string('shift_type')->nullable();         // Tipo de turno
            $table->string('event')->nullable();              // Evento relacionado
            $table->integer('downtime_count')->default(0);    // Contador de inactividad
            $table->integer('optimal_production_time')->nullable(); // Tiempo óptimo de producción
            $table->integer('reduced_speed_time_multiplier')->nullable(); // Multiplicador de velocidad reducida

            // Campos adicionales para RFID
            $table->string('epc');                  // Identificador EPC del grupo RFID
            $table->string('tid')->unique();        // Identificador único TID para cada lectura avanzada
            $table->integer('rssi')->nullable();    // Intensidad de señal (RSSI)
            $table->string('serialno')->nullable(); // Número de serie del dispositivo RFID
            
            $table->boolean('send_alert')->default(false); // Campo para habilitar/deshabilitar alertas
            $table->boolean('search_out')->default(false); // Campo para habilitar/deshabilitar monitorización de salida
            $table->string('last_ant_detect')->nullable();
            $table->string('last_status_detect')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfid_details');
    }
}
