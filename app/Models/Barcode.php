<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderStat;  // Asegúrate de importar el modelo OrderStat
use Illuminate\Support\Facades\Log;

class Barcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'name',
        'token',
        'mqtt_topic_barcodes',
        'machine_id',
        'ope_id',
        'order_notice',
        'last_barcode',
        'ip_zerotier',
        'user_ssh',
        'port_ssh',
        'ip_barcoder',
        'user_ssh_password',
        'port_barcoder',
        'conexion_type',
        'iniciar_model',
        'sended',
    ];

    protected static function boot()
    {
        parent::boot();

        // Evento 'updating' para detectar cambios en 'order_notice'
        static::updating(function ($barcode) {
            if ($barcode->isDirty('order_notice')) {
                self::processOrderNotice($barcode);
            }

            if ($barcode->isDirty([
                'ip_barcoder', 
                'port_barcoder',
            ])) {
                self::restartSupervisor();
            }
        });

        static::created(function ($barcode) {
            self::restartSupervisor();
        });

        static::deleted(function ($barcode) {
            self::restartSupervisor();
        });
    }

    /**
     * Método para procesar el campo 'order_notice' cuando cambia y asi se genera nueva linea de order_stats donde se guardan todos los infos del orden en curso
     */
    protected static function processOrderNotice($barcode)
    {
        // Decodificar el JSON almacenado en 'order_notice'
        $orderNotice = json_decode($barcode->order_notice, true);

        if ($orderNotice && isset($orderNotice['orderId'], $orderNotice['quantity'])) {
            
            // Extraer 'orderId' como cadena de texto completa
            $orderId = (string)$orderNotice['orderId'];  // Convertir a string explícitamente
            // Extraer 'quantity' del JSON esto no es necesario en string, por no tener caracteros raros
            $units = $orderNotice['quantity'];
            // Extraer 'uds' del primer nivel dentro de 'groupLevel'
            $box = $orderNotice['refer']['groupLevel'][0]['uds']; // Accede al valor de 'uds'
            
            // Extraer 'production_line_id' de la tabla 'barcodes'
            $productionLineId = $barcode->production_line_id;

            // Crear una nueva línea en 'order_stats' con los datos extraídos
            OrderStat::create([
                'production_line_id' => $productionLineId,
                'order_id' => $orderId,
                'box' => $box,
                'units_box' => $units,
                'units' => $box * $units,
                'units_per_minute_real' => null,  // Dejar vacíos o nulos según tus requisitos
                'units_per_minute_theoretical' => null,
                'seconds_per_unit_real' => null,
                'seconds_per_unit_theoretical' => null,
                'units_made_real' => 0,
                'units_made_theoretical' => 0,
                'sensor_stops_count' => 0,
                'sensor_stops_time' => 0,
                'production_stops_time' => 0,
                'units_made' => 0,
                'units_pending' => 0,
                'units_delayed' => 0,
                'slow_time' => 0,
                'oee' => null,  // Dejar vacío o nulo según corresponda
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

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
