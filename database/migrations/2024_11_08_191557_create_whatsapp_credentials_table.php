<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_credentials', function (Blueprint $table) {
            $table->id();
            $table->text('creds'); // Credenciales de autenticación en formato JSON
            $table->text('keys');  // Claves de sesión u otra configuración en formato JSON
            $table->timestamps();  // Campos de created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_credentials');
    }
}
