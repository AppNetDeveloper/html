<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSendedToBarcodesTable extends Migration
{
    public function up()
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->integer('sended')->default(0)->after('iniciar_model');
        });
    }

    public function down()
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropColumn('sended');
        });
    }
}

