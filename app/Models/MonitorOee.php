<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MonitorOee extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'production_line_id',
        'sensor_active',
        'modbus_active',
        'mqtt_topic',
        'mqtt_topic2',
        'topic_oee',
        'time_start_shift',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($monitorOee) {

            if ($monitorOee->isDirty([
                'mqtt_topic', 
                'mqtt_topic2',
                'topic_oee',
                'production_line_id', 
                'sensor_active', 
                'modbus_active',
            ])) {
                self::restartSupervisor();
            }
        });

        static::created(function ($monitorOee) {
            self::restartSupervisor();
        });

        static::deleted(function ($monitorOee) {
            self::restartSupervisor();
        });
    }

    /**
     * Método para reiniciar el Supervisor.
     */
    protected static function restartSupervisor()
    {
        try {
            exec('sudo /usr/bin/supervisorctl restart all', $output, $returnVar);

            if ($returnVar === 0) {
                Log::channel('supervisor')->info("Supervisor reiniciado exitosamente.");
            } else {
                Log::channel('supervisor')->error("Error al reiniciar supervisor: " . implode("\n", $output));
            }
        } catch (\Exception $e) {
            Log::channel('supervisor')->error("Excepción al reiniciar supervisor: " . $e->getMessage());
        }
    }
}
