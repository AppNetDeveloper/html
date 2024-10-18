<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class Sensor extends Model
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
        'name', // nombre del sensor
        'token', // código único que se le aloja a cada sensor
        'production_line_id', // el id de la línea de producción para asociar el sensor
        'barcoder_id', // asociamos el sensor con el barcoder
        'sensor_type', // 0 es sensor de conteo, 1 es sensor de consumibles, 2 de materia prima, 3 de avería en proceso
        'optimal_production_time', // tiempo óptimo para este orderId para cada caja/paquete/malla
        'reduced_speed_time_multiplier', // tiempo que es puesto como velocidad lenta, superior a este es parada
        'json_api', // valor de la API para obtener el valor del sensor, si no se pone nada es por defecto value
        'mqtt_topic_sensor', // valor del tópico para recibir el valor del sensor
        'count_total', // contador total 
        'count_total_0', // contador total 0
        'count_total_1', // contador total 1
        'count_shift_0', // contador de turno 0
        'count_shift_1', // contador de turno 1  
        'count_order_0', // contador de pedido 0
        'count_order_1', // contador de pedido 1
        'mqtt_topic_1', // tópico para el valor 1
        'function_model_0', // función para el valor 0
        'function_model_1', // función para el valor 1
        'invers_sensors', // si el sensor es inverso o no
        'downtime_count', // tiempo no productivo, se debe resetear en cada cambio de turno o pedido
        'unic_code_order', // código de orden único, uso interno
        'shift_type', // tipo de turno
    ];
    

    /**
     * Relación con la tabla ProductionLine.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con la tabla ControlWeight.
     */
    public function controlWeights()
    {
        return $this->hasMany(ControlWeight::class);
    }

    /**
     * Relación con la tabla ControlHeight.
     */
    public function controlHeights()
    {
        return $this->hasMany(ControlHeight::class);
    }

    /**
     * Relación con la tabla Modbus.
     */
    public function modbuses()
    {
        return $this->hasMany(Modbus::class);
    }

    /**
     * Relación con la tabla Barcode.
     */
    public function barcoder()
    {
        return $this->belongsTo(Barcode::class, 'barcoder_id');
    }
    public function sensorCounts()
    {
        return $this->hasMany(SensorCount::class, 'sensor_id');
    }
    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();

        // Evento 'updating' para detectar cambios
        static::updating(function ($sensor) {
            if ($sensor->isDirty([
                'mqtt_topic_sensor', 
                'mqtt_topic_1',
            ])) {
                self::restartSupervisor();
            }
        });

        static::created(function ($sensor) {
            
             // Generar un token único antes de crear el registro
             $sensor->token = Str::uuid(); // O Str::random(32) para un token aleatorio de 32 caracteres
             self::restartSupervisor();
        });

        static::deleted(function ($sensor) {
            self::restartSupervisor();
        });
    }
    public function generateUniqueCode()
    {
        // Obtener el id de la línea de producción
        $lineId = $this->production_line_id;
    
        // Obtener el id del sensor
        $sensorId = $this->id;
    
        // Obtener la fecha y hora actual en formato numérico
        $timestamp = Carbon::now()->format('YmdHis'); // Formato: YYYYMMDDHHMMSS
    
        // Concatenar el id de la línea, el id del sensor y el timestamp
        return $lineId . '_' . $sensorId . '_' . $timestamp;
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

