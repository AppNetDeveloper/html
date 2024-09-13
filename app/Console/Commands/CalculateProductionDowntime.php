<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\DowntimeSensor;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\SensorCount;

class CalculateProductionDowntime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-production-downtime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the production downtime for each sensor and handle downtime counts per shift, and send MQTT messages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $this->info("Starting the calculation of production downtime...");

            // Obtener todos los sensores
            $sensors = Sensor::all();
            
            foreach ($sensors as $sensor) {
                // Manejar la lógica según el tipo de sensor
                if ($sensor->sensor_type == 0) {
                    $this->handleType0DowntimeLogic($sensor);
                } elseif ($sensor->sensor_type == 1) {
                    $this->handleType1DowntimeLogic($sensor);
                } elseif ($sensor->sensor_type == 2) {
                    $this->handleType2DowntimeLogic($sensor);
                }
            }

            // Esperar 1 segundo antes de volver a ejecutar la lógica
            $this->info("Waiting for 1 second before the next run...");
            sleep(1); // Pausar 1 segundos 
        }

        return 0;
    }

    private function handleType0DowntimeLogic($sensor)
    {
        // Calcular el tiempo máximo permitido
        $optimalTime = $sensor->optimal_production_time ?? 30;
        $multiplier = $sensor->reduced_speed_time_multiplier ?? 1;
        $maxTime = $optimalTime * $multiplier;

        // Buscar el último registro en sensor_counts con value = 1
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)
            ->where('value', '1')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($sensorCount) {
            $lastEventTime = Carbon::parse($sensorCount->created_at);
            $now = Carbon::now();
            $timeDifference = $now->diffInSeconds($lastEventTime);

            if ($timeDifference > $maxTime) {
                // Se amplía la inactividad, mandamos mensaje con status=0
                $this->info("Sensor {$sensor->name} is in downtime.");
                $this->incrementDowntime($sensor, $timeDifference - $maxTime);
                $this->sendMqttMessage($sensor, 0, 0); // status 0 para ampliado, tipo 0
            } else {
                // Estable (no se amplía), mandamos mensaje con status=2
                $this->info("Sensor {$sensor->name} is stable.");
                $this->closeDowntime($sensor);
                $this->sendMqttMessage($sensor, 2, 0); // status 2 para estable, tipo 0
            }
        }
    }

    private function handleType1DowntimeLogic($sensor)
    {
        // Buscar el último registro en sensor_counts con el sensor_id
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)
        ->orderBy('created_at', 'desc')
        ->first();

        if ($sensorCount && $sensorCount->value == 1) {
        // El valor es 1, lo que significa que el sensor está en inactividad
        $lastEventTime = Carbon::parse($sensorCount->created_at);
        $now = Carbon::now();
        $timeDifference = $now->diffInSeconds($lastEventTime);

        // Se amplía la inactividad, mandamos mensaje con status=0
        $this->info("Sensor {$sensor->name} is in downtime.");
        $this->incrementDowntime($sensor, $timeDifference);  // Ajuste para no restar $maxTime aquí
        $this->sendMqttMessage($sensor, 0, $sensor->sensor_type); // status 0 para ampliado, tipo según el sensor
        } else {
        // El valor no es 1, lo que significa que el sensor está estable
        $this->info("Sensor {$sensor->name} is stable.");
        $this->closeDowntime($sensor);
        $this->sendMqttMessage($sensor, 2, $sensor->sensor_type); // status 2 para estable
        }

    }

    private function handleType2DowntimeLogic($sensor)
    {
        // Buscar el último registro en sensor_counts con el sensor_id
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($sensorCount && $sensorCount->value == 1) {
            // El valor es 1, lo que significa que el sensor está en inactividad
            $lastEventTime = Carbon::parse($sensorCount->created_at);
            $now = Carbon::now();
            $timeDifference = $now->diffInSeconds($lastEventTime);

            // Se amplía la inactividad, mandamos mensaje con status=0
            $this->info("Sensor {$sensor->name} is in downtime.");
            $this->incrementDowntime($sensor, $timeDifference);  // Ajuste para no restar $maxTime aquí
            $this->sendMqttMessage($sensor, 0, $sensor->sensor_type); // status 0 para ampliado, tipo según el sensor
        } else {
            // El valor no es 1, lo que significa que el sensor está estable
            $this->info("Sensor {$sensor->name} is stable.");
            $this->closeDowntime($sensor);
            $this->sendMqttMessage($sensor, 2, $sensor->sensor_type); // status 2 para estable
        }

    }

    private function incrementDowntime($sensor, $downtimeTime)
    {
        $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
            ->whereNull('end_time')
            ->first();

        if ($downtime) {
            $downtime->count_time += $downtimeTime;
            $downtime->save();
            $this->info("Updated downtime for sensor: {$sensor->name}. Incremented count_time by {$downtimeTime} seconds.");
        } else {
            DowntimeSensor::create([
                'sensor_id' => $sensor->id,
                'start_time' => Carbon::now(),
                'count_time' => $downtimeTime,
                'end_time' => null,
            ]);
            $this->info("New downtime record created for sensor: {$sensor->name}.");
        }

        $sensor->downtime_count++;
        $sensor->save();
        $this->info("Downtime count for sensor {$sensor->name} incremented to {$sensor->downtime_count}.");
    }

    private function closeDowntime($sensor)
    {
        $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
            ->whereNull('end_time')
            ->first();

        if ($downtime) {
            $downtime->end_time = Carbon::now();
            $downtime->save();
            $this->info("Downtime for sensor {$sensor->name} ended.");
        }
    }

    private function sendMqttMessage($sensor, $status, $sensorType)
    {
        // Extraer el production_line_id y mqtt_topic del sensor
        $productionLineId = $sensor->production_line_id;
        $mqttTopic = $sensor->mqtt_topic_1;

        // Formatear el mqtt_topic eliminando la parte '/mac/...'
        $topicBase = preg_replace('/\/mac\/[^\/]+$/', '', $mqttTopic);
        $this->info("Formatted MQTT topic: {$topicBase}");

        // Sumar downtime_count de todos los sensores con el mismo production_line_id y el sensor_type actual
        $totalDowntimeCount = Sensor::where('production_line_id', $productionLineId)
            ->where('sensor_type', $sensorType) // Filtrar por el tipo de sensor actual
            ->sum('downtime_count') * 1000; // Convertir a milisegundos

        // Crear el mensaje JSON
        $message = json_encode([
            'value' => $totalDowntimeCount,
            'status' => $status // status = 0 cuando se amplía, status = 2 cuando es estable
        ]);

        // Publicar el mensaje MQTT usando la función de publicación
        $this->publishMqttMessage($topicBase . "/waitTime", $message);
    }

    private function publishMqttMessage($topic, $message)
    {
        try {
            // Inserta en la tabla mqtt_send_server1
            MqttSendServer1::createRecord($topic, $message);

            // Inserta en la tabla mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);

            $this->info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");
        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }
}
