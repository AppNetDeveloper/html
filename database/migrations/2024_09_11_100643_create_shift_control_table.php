<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftControlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_control', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('production_line_id')->nullable()->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('modbus_id')->nullable()->constrained('modbuses')->onDelete('cascade'); // Clave foránea para modbuses
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->onDelete('cascade'); // Clave foránea para sensors
            $table->string('mqtt_topic');
            $table->string('shift_type'); // Could be enum if you want to limit options
            $table->string('event'); // Stores the event associated with the shift
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
        Schema::dropIfExists('shift_control');
    }
}
