<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorOeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_oees', function (Blueprint $table) {
            $table->id(); // ID de la tabla

            // Clave foránea con la tabla 'production_lines' que puede ser nullable
            $table->foreignId('production_line_id')->nullable()->constrained('production_lines')->onDelete('set null');

            // Cambiar sensor_id y modbus_id a booleanos (activo/inactivo) y nulables
            $table->boolean('sensor_active')->nullable()->default(null); // 0: Inactivo, 1: Activo, null: No aplicable
            $table->boolean('modbus_active')->nullable()->default(null); // 0: Inactivo, 1: Activo, null: No aplicable

            // Campo para el MQTT topic
            $table->string('mqtt_topic');

            // Campos de timestamps: 'created_at' y 'updated_at'
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
        Schema::dropIfExists('monitor_oees');
    }
}
