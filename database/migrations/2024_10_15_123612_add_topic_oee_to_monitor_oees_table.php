<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTopicOeeToMonitorOeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monitor_oees', function (Blueprint $table) {
            $table->string('topic_oee')->nullable(); // Añade la columna como nullable
        });
    }

    public function down()
    {
        Schema::table('monitor_oees', function (Blueprint $table) {
            $table->dropColumn('topic_oee'); // Elimina la columna en caso de rollback
        });
    }

}
