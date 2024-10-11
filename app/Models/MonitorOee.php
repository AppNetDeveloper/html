<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\OrderStat;  // Asegúrate de importar el modelo OrderStat

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
            // Verificar si el campo 'modbus_active' ha cambiado
            if ($monitorOee->isDirty('modbus_active')) {
                self::handleModbusActiveChange($monitorOee->production_line_id);
            }
            
            if ($monitorOee->isDirty([
                'mqtt_topic', 
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
     * Método para gestionar el cambio en el campo 'modbus_active'
     */
    protected static function handleModbusActiveChange($productionLineId)
    {
        // Buscar la última entrada en 'order_stats' con el mismo 'production_line_id'
        $lastOrderStat = OrderStat::where('production_line_id', $productionLineId)->latest()->first();

       

        if ($lastOrderStat) {
             // Calcular las unidades restantes (units - units_made_real)
            $unitsRemaining = $lastOrderStat->units - $lastOrderStat->units_made_real;
            
            // Crear una nueva línea con los campos necesarios y los demás vacíos o en 0
            OrderStat::create([
                'production_line_id' => $lastOrderStat->production_line_id,
                'order_id' => $lastOrderStat->order_id,
                'units' => $unitsRemaining,  // Aquí asignamos lo que queda por fabricar
                'units_per_minute_real' => null,  // Dejar estos campos vacíos o nulos
                'units_per_minute_theoretical' => null,
                'seconds_per_unit_real' => null,
                'seconds_per_unit_theoretical' => null,
                'units_made_real' => 0,
                'units_made_theoretical' => 0,
                'sensor_stops_count' => 0,
                'sensor_stops_time' => 0,
                'production_stops_count' => 0,
                'production_stops_time' => 0,
                'units_made' => 0,
                'units_pending' => 0,
                'units_delayed' => 0,
                'slow_time' => 0,
                'oee' => null,  // Dejar vacío o nulo
            ]);
        }
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
