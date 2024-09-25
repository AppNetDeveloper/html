<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('modbuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('production_line_id');
            $table->foreign('production_line_id')->references('id')->on('production_lines');
            $table->text('json_api')->nullable(); //identificar el valor de la api
            $table->string('mqtt_topic_modbus')->nullable();    //esto para extrar de mqtt en lugar de api
            $table->string('mqtt_topic')->nullable(); //esto para mandar las info por topico, se adapta en codigo para ser gross control etc
            $table->string('token')->nullable();    //token para el api
            $table->string('dimension_id')->nullable(); //el id del modbus que se esta usando en linia de pesaje para medir altura
            $table->string('dimension')->nullable(); //la dimension del bulto si tiene un medidor de altura anadido
            $table->string('max_kg')->nullable();   //maximo kg que se han recivido de una linea entre los 0 y 0 para resetear
            $table->string('rep_number')->nullable(); //numero de repeticion que se ha recivido de una linea entre los 0 y 0    para poner el valor como max
            $table->string('tara')->nullable()->default('0'); //el peso del palet o para hacer un 0 por software
            $table->string('tara_calibrate')->nullable()->default('0'); //tara para hacer o automatico
            $table->string('calibration_type')->nullable()->default('0'); //tara maxima para hacer o automatico
            $table->string('conversion_factor')->nullable()->default('10'); //conversion_factor es si la bascula va en gramos o kg si se parte a 10 o 100
            $table->string('total_kg_order')->nullable()->default('0'); //total kg por order
            $table->string('total_kg_shift')->nullable()->default('0'); //total kg por turno
            $table->string('min_kg')->nullable(); //minimo kg que activan el contador
            $table->string('last_kg')->nullable(); //ultimo kg que se ha recivido de una linea mayor de min_kg
            $table->string('last_rep')->nullable(); //numero de repeticion que se ha recivido en un valor estable de last_kg
            $table->string('rec_box')->nullable(); //numero de cajas que se han hecho es un recuento por order notice
            $table->string('rec_box_shift')->nullable(); //numero de cajas que se han hecho es un recuento por shift
            $table->string('rec_box_unlimited')->nullable(); //numero de cajas que se han realizado sin resetear nunca
            $table->string('last_value')->nullable(); //el ultimo valor de la modbus
            $table->string('variacion_number')->nullable(); // variaciones que es permitido entre cada lectura para ponerse estable.
            $table->string('model_name')->nullable(); //con esto identificamos si es bascula o otro sensor
            $table->string('dimension_default')->nullable(); //esto es la medida en vacio del medidor laser
            $table->string('dimension_max')->nullable(); //medida maxica alcanzada despues de salir del valor default
            $table->string('dimension_variacion')->nullable(); //medida de variacion entre cada lectura de la modbus para que se pueda hacer el contador.
            $table->string('offset_meter')->nullable()->default('0');  // offset para medidor láser o ultra sonidos, default '0'
            $table->string('printer_id')->nullable();  //id de la impresora que se va a usar para imprimir el valor barcoder del bulto anonimo
            $table->string('unic_code_order')->nullable();  // Nuevo campo unic_code_order
            $table->string('shift_type')->nullable();  // Tipo de turno
            $table->string('event')->nullable();       // Evento
            $table->integer('downtime_count')->default(0);  // Nuevo campo para contar inactividad
            $table->integer('optimal_production_time')->nullable(); // Tiempo óptimo de producción de una caja
            $table->integer('reduced_speed_time_multiplier')->nullable(); // Multiplicador para velocidad reducida
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('modbuses');
    }
};
