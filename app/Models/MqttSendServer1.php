<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqttSendServer1 extends Model
{
    use HasFactory;

    // Especifica la tabla asociada a este modelo
    protected $table = 'mqtt_send_server1';

    // Define los campos que se pueden asignar masivamente
    protected $fillable = ['topic', 'json_data'];
    public static function createRecord($topic, $jsonData)
    {
        return self::create([
            'topic' => $topic,
            'json_data' => is_array($jsonData) ? json_encode($jsonData) : $jsonData,
        ]);
    }
}
