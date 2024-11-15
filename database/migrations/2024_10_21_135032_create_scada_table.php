<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScadaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines');
            $table->string('name');
            $table->uuid('token')->unique();  // UUID para token único
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
        Schema::dropIfExists('scada');
    }
}
