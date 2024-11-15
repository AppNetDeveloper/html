<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBluetoothDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bluetooth_details', function (Blueprint $table) {
            $table->id();  // Esto maneja automáticamente el autoincremento desde Laravel
            $table->string('name'); // Nombre descriptivo o ubicación
            $table->string('token')->unique(); // Token único para cada entrada
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('bluetooth_reading_id')->constrained('bluetooth_readings')->onDelete('cascade'); // Relación con `bluetooth_readings`
            $table->integer('bluetooth_type')->default(0); // Tipo de Bluetooth
            $table->integer('count_total')->default(0);
            $table->integer('count_total_0')->default(0);
            $table->integer('count_total_1')->default(0);
            $table->integer('count_shift_0')->default(0);
            $table->integer('count_shift_1')->default(0);
            $table->integer('count_order_0')->default(0);
            $table->integer('count_order_1')->default(0);
            $table->string('mqtt_topic_1'); // Otro tópico MQTT
            $table->string('function_model_0');
            $table->string('function_model_1');
            $table->boolean('invers_sensors')->default(false);
            $table->string('unic_code_order')->nullable();
            $table->string('shift_type')->nullable();
            $table->string('event')->nullable();
            $table->integer('downtime_count')->default(0);
            $table->integer('optimal_production_time')->nullable();
            $table->integer('reduced_speed_time_multiplier')->nullable();
            $table->string('mac'); // Dirección MAC del dispositivo Bluetooth
            $table->boolean('send_alert')->default(true); // Campo para habilitar/deshabilitar alertas
            $table->boolean('search_out')->default(true); // Campo para habilitar/deshabilitar monitorización de salida
            $table->string('last_ant_detect')->nullable();
            $table->string('last_status_detect')->nullable();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('bluetooth_details');
    }
    
}
