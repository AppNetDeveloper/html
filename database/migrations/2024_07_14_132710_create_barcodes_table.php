<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id'); // Id de linea de produccion es una key
            $table->string('name'); // nombre del lectura de codigo de barras
            $table->string('token')->unique(); //token unico
            $table->string('mqtt_topic_barcodes')->nullable(); //el mqtt topic donde segeneran los otros topicos par escuchar y mandar 
            $table->string('machine_id')->nullable(); // Permite valores nulos, es el id de la maquina
            $table->string('ope_id')->nullable();    // Permite valores nulos,  es ENVASADO etc
            $table->json('order_notice')->nullable(); // Almacena datos JSONm esto es el json entero del pedido en curso
            $table->string('last_barcode')->nullable(); // el ultimo barcode leido
            $table->string('ip_zerotier')->nullable();
            $table->string('iniciar_model')->default('INICIAR')->after('conexion_type');
            $table->integer('sended')->default(0)->after('iniciar_model');
            $table->string('user_ssh')->nullable();
            $table->string('port_ssh')->nullable();
            $table->string('user_ssh_password')->nullable();
            $table->string('ip_barcoder')->nullable();
            $table->string('port_barcoder')->nullable();
            $table->string('conexion_type')->nullable()->default(1);
            $table->timestamps();

            $table->foreign('production_line_id')->references('id')->on('production_lines');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barcodes');
    }
}
