<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_stats', function (Blueprint $table) {
            // Clave primaria autoincremental, necesaria para identificar cada registro de manera única.
            $table->id();  

            // 'production_line_id' es una clave foránea que conecta con la tabla 'production_lines' para identificar a qué línea de producción pertenece.
            $table->unsignedBigInteger('production_line_id')->nullable();
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');

            // 'order_id' es el identificador de la orden de producción relacionada.
            $table->string('order_id', 255)->nullable();  // Acepta cadenas alfanuméricas como '12/4611'

            // box es el numero de cajas por order
            $table->integer('box')->nullable(); //cajas por orden

            //units_box es el numero de unidades por caja
            $table->integer('units_box')->nullable(); // unidades por caja

            // 'units' representa el número de unidades que se deben fabricar en esa orden. Se usa 'integer' porque es un número entero.
            $table->integer('units')->nullable();  // Número total de unidades. por oorden

            // 'units_per_minute_real' representa las unidades producidas por minuto en la realidad. Se usa 'decimal' para permitir fracciones de unidades por minuto.
            $table->decimal('units_per_minute_real', 8, 2)->nullable();  // Unidades por minuto real (puede incluir decimales).

            // 'units_per_minute_theoretical' representa la cantidad teórica de unidades por minuto. Igual que el anterior, es decimal para permitir cálculos precisos.
            $table->decimal('units_per_minute_theoretical', 8, 2)->nullable();  // Unidades por minuto teórico (con decimales).

            // 'seconds_per_unit_real' es el tiempo real que se tarda en producir una unidad, medido en segundos. Como puede incluir decimales, usamos 'decimal'.
            $table->decimal('seconds_per_unit_real', 8, 2)->nullable();  // Segundos por unidad real.

            // 'seconds_per_unit_theoretical' es el tiempo teórico por unidad. También es un valor con posible decimal, por lo que se usa 'decimal'.
            $table->decimal('seconds_per_unit_theoretical', 8, 2)->nullable();  // Segundos por unidad teórico.

            // 'units_made_real' es el número total de unidades fabricadas en la realidad, se usa 'integer' porque es un número entero.
            $table->integer('units_made_real')->nullable();  // Unidades fabricadas reales a esta momento.

            // 'units_made_theoretical' es el número teórico de unidades que deberían haberse fabricado.
            $table->integer('units_made_theoretical')->nullable(); ;  // Unidades fabricadas teóricas a esta momento .

            // 'sensor_stops_count' cuenta cuántas veces la producción se ha detenido debido a problemas con los sensores. Es un número entero, por lo que usamos 'integer'.
            $table->integer('sensor_stops_count')->nullable();  // Número de paradas justificadas por sensores en materia prima consumible 

            // 'sensor_stops_time' representa el tiempo total de paradas causadas por sensores, en segundos. Se usa 'integer' ya que los tiempos suelen ser representados en números enteros (segundos).
            $table->integer('sensor_stops_time')->nullable();  // Tiempo total de paradas por sensores (en segundos), a paradas por cambio pegatinas mallas cajas etc

            // 'production_stops_time' es el tiempo total que la producción ha estado parada, en minutos.
            $table->integer('production_stops_time')->nullable();  // Tiempo total de paradas de producción (en minutos), en linea de produccion

            // 'units_made' es el número de unidades que realmente han sido fabricadas hasta el momento.
            $table->integer('units_made')->nullable();  // Unidades fabricadas hasta este momento

            // 'units_pending' es el número de unidades que aún faltan por fabricar.
            $table->integer('units_pending')->nullable();  // Unidades pendientes. hasta esto momento

            // 'units_delayed' cuenta las unidades que no se han fabricado a tiempo, es decir, que están atrasadas.
            $table->integer('units_delayed')->nullable();  // Unidades atrasadas. hasta este momento

            // 'slow_time' es contador de cajas que han superado la velocidad optimima pero en el tiempo permitido , en segundos.
            $table->integer('slow_time')->nullable();  // Tiempo de producción lenta.

            // 'fast_time' es contador de cajas que se han fabricado en tiempo optimo , en segundos.
            $table->integer('fast_time')->nullable();  // Tiempo de producción lenta.

            // 'out_time' es contador de cajas que han superado la velocidad optimima y velocidad reducida , en segundos.
            $table->integer('out_time')->nullable();  // Tiempo de producción lenta.

            //  'theoretical_end_time' es el tiempo teórico de fin de orden, en minutos.
            $table->integer('theoretical_end_time')->nullable();  // Tiempo teórico de fin de orden.

            // 'real_end_time' es el tiempo real de fin de orden, en minutos.
            $table->integer('real_end_time')->nullable();  // Tiempo real de fin de orden.  

            // 'oee' es el porcentaje de eficiencia operativa (Overall Equipment Effectiveness), y se expresa como un valor decimal que puede ser, por ejemplo, 95.50 (95.5% de eficiencia).
            $table->decimal('oee', 5, 2)->nullable();  // OEE (Overall Equipment Effectiveness) como porcentaje.

            // Timestamps automáticos para 'created_at' y 'updated_at'.
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
        Schema::dropIfExists('order_stats');
    }
}
