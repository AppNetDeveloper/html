<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DowntimeSensor extends Model
{
    use HasFactory;

    // Definir la tabla asociada si el nombre no sigue el plural estándar
    protected $table = 'downtime_sensors';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'sensor_id',
        'start_time',
        'end_time',
        'count_time',
    ];

    // Relación con Sensor
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}
