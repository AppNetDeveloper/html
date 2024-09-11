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
            $table->foreignId('production_line_id')->constrained('production_lines');
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
