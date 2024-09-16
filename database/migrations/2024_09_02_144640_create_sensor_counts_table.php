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
            $table->string('name');      // Nombre del sensor
            $table->string('value');     // Valor del sensor en la base de datos
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');  // Clave foránea con la linia de produccion
            $table->foreignId('sensor_id')->constrained('sensors')->onDelete('cascade')->after('id');      // Clave foránea con la tabla 'sensors'
            $table->string('model_product');  // Identificación del producto que se fabrica 
            $table->string('orderId')->nullable();  // Identificación del pedido
            $table->integer('count_total')->default(0);  // contador total de lecturas
            $table->integer('count_total_0')->default(0);         // contador total de lecturas con valor inactivo
            $table->integer('count_total_1')->default(0);         // contador total de lecturas con valor activo
            $table->integer('count_shift_0')->default(0);         // contador de lecturas por shift con valor inactivo
            $table->integer('count_shift_1')->default(0);         // contador de lecturas por shift con valor activo
            $table->integer('count_order_0')->default(0);         // contador de lecturas por order con valor inactivo
            $table->integer('count_order_1')->default(0);         // contador de lecturas por order con valor activo
            $table->bigInteger('time_00')->nullable(); // Diferencia en segundos o milisegundos entre inactivo a activo
            $table->bigInteger('time_01')->nullable();  // Diferencia en segundos o milisegundos entre inactivo a activo
            $table->bigInteger('time_11')->nullable();  // Diferencia en segundos o milisegundos entre activo a activo
            $table->bigInteger('time_10')->nullable();  // Diferencia en segundos o milisegundos entre activo a inactivo
            $table->string('unic_code_order')->nullable();  // Nuevo campo unic_code_order que se resetea por shift
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

