<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        'name',
        'production_line_id', //el id de la linia de produccion asi se asocia el sensor
        'barcoder_id', // asociamos el sensor con el barcoder
        'sensor_type',      // 0 es sensor de conteo , 1 es sensor de consumibles, 2 de materia prima, 3 de averia en proceso
        'optimal_production_time', //tiempo optimo para este orderId para cada caja paquete malla
        'reduced_speed_time_multiplier',//tiempo que es puesto como velocidad lenta, todo que es superior  ESTE ES PARADA
        'json_api', // valor de la api para obtener el valor del sensor si no se pone nada es por defecto value
        'mqtt_topic_sensor',    // valor del topic para recibir el valor del sensor
        'count_total',          // contador total
        'count_total_0',        // contador total 0
        'count_total_1',        // contador total 1
        'count_shift_0',        // contador de turno 0
        'count_shift_1',        // contador de turno 1  
        'count_order_0',        // contador de pedido 0
        'count_order_1',        // contador de pedido 1
        'mqtt_topic_0',         // topic para el valor 0
        'mqtt_topic_1',         // topic para el valor 1
        'function_model_0',     // funcion para el valor 0
        'function_model_1',     // funcion para el valor 1
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
    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($sensor) {
            if ($sensor->isDirty([
                'mqtt_topic_sensor', 
                'mqtt_topic_0', 
                'mqtt_topic_1',
            ])) {
                self::restartSupervisor();
            }
        });

        static::created(function ($sensor) {
            self::restartSupervisor();
        });

        static::deleted(function ($sensor) {
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
