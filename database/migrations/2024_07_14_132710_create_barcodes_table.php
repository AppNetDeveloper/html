<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger
            $table->unsignedBigInteger('production_line_id'); 
            $table->string('name');
            $table->string('token')->unique();
            $table->string('mqtt_topic_barcodes')->nullable();
            $table->string('machine_id')->nullable();
            $table->string('ope_id')->nullable();
            $table->json('order_notice')->nullable();
            $table->string('last_barcode')->nullable();
            $table->string('ip_zerotier')->nullable();
            $table->string('iniciar_model')->default('INICIAR');
            $table->integer('sended')->default(0);
            $table->string('user_ssh')->nullable();
            $table->string('port_ssh')->nullable();
            $table->string('user_ssh_password')->nullable();
            $table->string('ip_barcoder')->nullable();
            $table->string('port_barcoder')->nullable();
            $table->string('conexion_type')->default(1)->nullable();
            $table->timestamps();

            $table->foreign('production_line_id')->references('id')->on('production_lines');
        });
    }

    public function down()
    {
        Schema::dropIfExists('barcodes');
    }
};
