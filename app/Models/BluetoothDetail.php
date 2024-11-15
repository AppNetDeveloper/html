<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BluetoothDetail extends Model
{
    use HasFactory;

    protected $table = 'bluetooth_details';

    protected $fillable = [
        'name',
        'token',               // Token único para cada entrada
        'production_line_id',  // ID de la línea de producción
        'bluetooth_reading_id', // Relación con el modelo BluetoothReading
        'bluetooth_type',      // Tipo de Bluetooth
        'count_total',         // Contador total de lecturas
        'count_total_0',       // Contador total de lecturas inactivas
        'count_total_1',       // Contador total de lecturas activas
        'count_shift_0',       // Contador de lecturas inactivas por turno
        'count_shift_1',       // Contador de lecturas activas por turno
        'count_order_0',       // Contador de lecturas inactivas por orden
        'count_order_1',       // Contador de lecturas activas por orden
        'mqtt_topic_1',        // Otro tópico MQTT
        'function_model_0',    // Función del modelo 0
        'function_model_1',    // Función del modelo 1
        'invers_sensors',      // Indicador de inversión de sensores
        'unic_code_order',     // Código único de orden
        'shift_type',          // Tipo de turno
        'event',               // Evento relacionado
        'downtime_count',      // Contador de inactividad
        'optimal_production_time', // Tiempo óptimo de producción
        'reduced_speed_time_multiplier', // Multiplicador para velocidad reducida
        'send_alert', //avisar si sale de alerta o entra
        'search_out', // buscar siempre si sale de perimetro
        'last_ant_detect',        // Nuevo campo
        'last_status_detect',     // Nuevo campo

        // Campos específicos de Bluetooth
        'mac'                 // Dirección MAC del dispositivo Bluetooth
    ];

    /**
     * Relación con el modelo BluetoothReading.
     */
    public function bluetoothReading()
    {
        return $this->belongsTo(BluetoothReading::class);
    }
}
