<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id');
            $table->time('start');
            $table->time('end');
            $table->timestamps();

            // Definir la clave foránea con la tabla production_lines
            $table->foreign('production_line_id')
                ->references('id')
                ->on('production_lines')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_list');
    }
}
