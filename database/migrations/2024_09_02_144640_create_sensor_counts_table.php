<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSensorCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sensor_counts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('value');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('sensor_id')->constrained('sensors')->onDelete('cascade')->after('id');
            $table->string('model_product');  // Identificación del producto que se fabrica
            $table->string('orderId')->nullable();
            $table->integer('count_total')->default(0);
            $table->integer('count_total_0')->default(0);
            $table->integer('count_total_1')->default(0);
            $table->integer('count_shift_0')->default(0);
            $table->integer('count_shift_1')->default(0);
            $table->integer('count_order_0')->default(0);
            $table->integer('count_order_1')->default(0);
            $table->bigInteger('time_00')->nullable(); // Diferencia en segundos o milisegundos
            $table->bigInteger('time_01')->nullable();
            $table->bigInteger('time_11')->nullable();
            $table->bigInteger('time_10')->nullable();
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
        Schema::dropIfExists('sensor_counts');
    }
}

