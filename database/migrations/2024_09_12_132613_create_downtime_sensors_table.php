<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDowntimeSensorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('downtime_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensors')->onDelete('cascade'); // Clave foránea con la tabla 'sensors'
            $table->dateTime('start_time');  // Tiempo de inicio de la inactividad
            $table->dateTime('end_time')->nullable();  // Tiempo de fin de la inactividad, puede ser nulo al iniciar
            $table->integer('count_time')->nullable();  // Contador de segundos de la inactividad
            $table->timestamps();  // created_at y updated_at automáticos
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('downtime_sensors');
    }
}
