<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenToSensorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('sensors', function (Blueprint $table) {
        $table->string('token')->unique()->after('id'); // Campo token único
    });
}

public function down()
{
    Schema::table('sensors', function (Blueprint $table) {
        $table->dropColumn('token');
    });
}

}
