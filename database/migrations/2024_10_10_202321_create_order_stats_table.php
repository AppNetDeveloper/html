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
            $table->unsignedBigInteger('production_line_id');
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');

            // 'order_id' es el identificador de la orden de producción relacionada.
            $table->unsignedBigInteger('order_id');

            // 'units' representa el número de unidades que se deben fabricar en esa orden. Se usa 'integer' porque es un número entero.
            $table->integer('units');  // Número total de unidades.

            // 'units_per_minute_real' representa las unidades producidas por minuto en la realidad. Se usa 'decimal' para permitir fracciones de unidades por minuto.
            $table->decimal('units_per_minute_real', 8, 2);  // Unidades por minuto real (puede incluir decimales).

            // 'units_per_minute_theoretical' representa la cantidad teórica de unidades por minuto. Igual que el anterior, es decimal para permitir cálculos precisos.
            $table->decimal('units_per_minute_theoretical', 8, 2);  // Unidades por minuto teórico (con decimales).

            // 'seconds_per_unit_real' es el tiempo real que se tarda en producir una unidad, medido en segundos. Como puede incluir decimales, usamos 'decimal'.
            $table->decimal('seconds_per_unit_real', 8, 2);  // Segundos por unidad real.

            // 'seconds_per_unit_theoretical' es el tiempo teórico por unidad. También es un valor con posible decimal, por lo que se usa 'decimal'.
            $table->decimal('seconds_per_unit_theoretical', 8, 2);  // Segundos por unidad teórico.

            // 'units_made_real' es el número total de unidades fabricadas en la realidad, se usa 'integer' porque es un número entero.
            $table->integer('units_made_real');  // Unidades fabricadas reales.

            // 'units_made_theoretical' es el número teórico de unidades que deberían haberse fabricado.
            $table->integer('units_made_theoretical');  // Unidades fabricadas teóricas.

            // 'sensor_stops_count' cuenta cuántas veces la producción se ha detenido debido a problemas con los sensores. Es un número entero, por lo que usamos 'integer'.
            $table->integer('sensor_stops_count');  // Número de paradas justificadas por sensores.

            // 'sensor_stops_time' representa el tiempo total de paradas causadas por sensores, en segundos. Se usa 'integer' ya que los tiempos suelen ser representados en números enteros (segundos).
            $table->integer('sensor_stops_time');  // Tiempo total de paradas por sensores (en segundos).

            // 'production_stops_count' cuenta cuántas veces la línea de producción ha estado parada. Se usa 'integer' para contar el número total de paradas.
            $table->integer('production_stops_count');  // Número de paradas de producción.

            // 'production_stops_time' es el tiempo total que la producción ha estado parada, en segundos.
            $table->integer('production_stops_time');  // Tiempo total de paradas de producción (en segundos).

            // 'units_made' es el número de unidades que realmente han sido fabricadas hasta el momento.
            $table->integer('units_made');  // Unidades fabricadas.

            // 'units_pending' es el número de unidades que aún faltan por fabricar.
            $table->integer('units_pending');  // Unidades pendientes.

            // 'units_delayed' cuenta las unidades que no se han fabricado a tiempo, es decir, que están atrasadas.
            $table->integer('units_delayed');  // Unidades atrasadas.

            // 'slow_time' es el tiempo total en el que la producción ha operado por debajo de su rendimiento óptimo, en segundos.
            $table->integer('slow_time');  // Tiempo de producción lenta.

            // 'oee' es el porcentaje de eficiencia operativa (Overall Equipment Effectiveness), y se expresa como un valor decimal que puede ser, por ejemplo, 95.50 (95.5% de eficiencia).
            $table->decimal('oee', 5, 2);  // OEE (Overall Equipment Effectiveness) como porcentaje.

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
