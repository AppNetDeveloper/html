<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MonitorOee extends Model
{
    use HasFactory;

    /**
     * Habilitar el manejo automático de timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Los atributos que son asignables.
     *
     * @var array
     */
    protected $fillable = [
        'production_line_id',   // ID de la línea de producción
        'sensor_active',    // si se monitoriza los sensores se pone a 1 si no 0
        'modbus_active',    // si se monitoriza los modbus se pone a 1 si no 0
        'mqtt_topic',           // MQTT Topic
    ];

    /**
     * Relación con la tabla ProductionLine.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con la tabla Sensor.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Relación con la tabla Modbus.
     */
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }

    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();

        // Evento 'updating' para detectar cambios en los campos que nos interesan
        static::updating(function ($monitorOee) {
            // Verificar si cambian los campos relacionados con MQTT o claves foráneas
            if ($monitorOee->isDirty([
                'mqtt_topic', 
                'production_line_id', 
                'sensor_active', 
                'modbus_active',
            ])) {
                self::restartSupervisor();
            }
        });

        // Evento 'created' para reiniciar supervisor cuando se crea un registro
        static::created(function ($monitorOee) {
            self::restartSupervisor();
        });

        // Evento 'deleted' para reiniciar supervisor cuando se borra un registro
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
            // Usa sudo para ejecutar supervisorctl sin contraseña
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
