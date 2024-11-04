<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarcodesTableAndAddRelations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Crear la tabla `barcodes`
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id'); // Llave foránea de línea de producción
            $table->string('name'); // Nombre de la lectura del código de barras
            $table->string('token')->unique(); // Token único
            $table->string('mqtt_topic_barcodes')->nullable(); // MQTT topic para escucha y envío de mensajes
            $table->string('machine_id')->nullable(); // ID de la máquina
            $table->string('ope_id')->nullable(); // ID de operación, por ejemplo ENVASADO
            $table->json('order_notice')->nullable(); // JSON completo del pedido en curso
            $table->string('last_barcode')->nullable(); // Último código de barras leído
            $table->string('ip_zerotier')->nullable();
            $table->string('iniciar_model')->default('INICIAR'); // Campo `iniciar_model`
            $table->integer('sended')->default(0); // Campo `sended`
            $table->string('user_ssh')->nullable();
            $table->string('port_ssh')->nullable();
            $table->string('user_ssh_password')->nullable();
            $table->string('ip_barcoder')->nullable();
            $table->string('port_barcoder')->nullable();
            $table->string('conexion_type')->default(1)->nullable();
            $table->timestamps();

            $table->foreign('production_line_id')->references('id')->on('production_lines');
        });

        // Agregar la columna `barcoder_id` en `modbuses` y la clave foránea
        Schema::table('modbuses', function (Blueprint $table) {
            $table->unsignedBigInteger('barcoder_id')->nullable();
            $table->foreign('barcoder_id')->references('id')->on('barcodes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir cambios en `modbuses`
        Schema::table('modbuses', function (Blueprint $table) {
            $table->dropForeign(['barcoder_id']);
            $table->dropColumn('barcoder_id');
        });

        // Eliminar la tabla `barcodes`
        Schema::dropIfExists('barcodes');
    }
}
