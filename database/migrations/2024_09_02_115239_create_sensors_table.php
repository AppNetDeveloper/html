<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSensorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('barcoder_id')->constrained('barcodes')->onDelete('cascade'); // Apunta a 'barcodes'
            $table->integer('sensor_type')->default(0);
            $table->integer('optimal_production_time')->nullable(); // Tiempo óptimo de producción de una caja
            $table->integer('reduced_speed_time_multiplier')->nullable(); // Multiplicador para velocidad reducida
            $table->json('json_api')->nullable(); // Campo json_api que puede ser nulo
            $table->string('mqtt_topic_sensor');
            $table->integer('count_total')->default(0);
            $table->integer('count_total_0')->default(0);
            $table->integer('count_total_1')->default(0);
            $table->integer('count_shift_0')->default(0);
            $table->integer('count_shift_1')->default(0);
            $table->integer('count_order_0')->default(0);
            $table->integer('count_order_1')->default(0);
            $table->string('mqtt_topic_1');
            $table->string('function_model_0');
            $table->string('function_model_1');
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
        Schema::dropIfExists('sensors');
    }
}