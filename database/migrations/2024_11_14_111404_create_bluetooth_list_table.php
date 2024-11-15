<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBluetoothListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bluetooth_list', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('value');
            $table->unsignedBigInteger('production_line_id');
            $table->unsignedBigInteger('bluetooth_detail_id');
            $table->unsignedBigInteger('bluetooth_reading_id');
            $table->string('bluetooth_ant_name');
            $table->string('model_product');
            $table->string('orderId')->nullable();
            $table->integer('count_total')->default(0);
            $table->integer('count_total_1')->default(0);
            $table->integer('count_shift_1')->default(0);
            $table->integer('count_order_1')->default(0);
            $table->bigInteger('time_11')->nullable();
            $table->string('mac'); // Dirección MAC del dispositivo Bluetooth
            $table->string('change')->nullable(); // Agregamos el campo `change`
            $table->string('rssi')->nullable(); // Agregamos el campo `change`
            $table->timestamps();
    
            // Definir las claves foráneas
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');
            $table->foreign('bluetooth_detail_id')->references('id')->on('bluetooth_details')->onDelete('cascade');
            $table->foreign('bluetooth_reading_id')->references('id')->on('bluetooth_readings')->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('bluetooth_list');
    }
    
}
