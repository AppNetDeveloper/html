<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRfidListTable extends Migration
{
    public function up()
    {
        Schema::create('rfid_list', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('value');
            $table->unsignedBigInteger('production_line_id');
            $table->unsignedBigInteger('rfid_detail_id');
            $table->unsignedBigInteger('rfid_reading_id');
            $table->string('rfid_ant_name');
            $table->string('model_product');
            $table->string('orderId')->nullable();
            $table->integer('count_total')->default(0);
            $table->integer('count_total_1')->default(0);
            $table->integer('count_shift_1')->default(0);
            $table->integer('count_order_1')->default(0);
            $table->bigInteger('time_11')->nullable();
            $table->string('epc');
            $table->string('tid');
            $table->integer('rssi')->nullable();
            $table->string('serialno')->nullable();
            $table->integer('ant')->nullable();
            $table->timestamps();
        
            // Definir las claves foráneas con `unsignedBigInteger`
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');
            $table->foreign('rfid_detail_id')->references('id')->on('rfid_details')->onDelete('cascade');
            $table->foreign('rfid_reading_id')->references('id')->on('rfid_readings')->onDelete('cascade');
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('rfid_list');
    }
}
