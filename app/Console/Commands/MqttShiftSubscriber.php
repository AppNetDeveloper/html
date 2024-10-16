<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon; // Asegúrate de importar Carbon para el timestamp
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\MonitorOee;
use Illuminate\Support\Facades\Log;
use App\Models\OrderStat;

class MqttShiftSubscriber extends Command
{
    protected $signature = 'mqtt:shiftsubscribe';
    protected $description = 'Subscribe to MQTT topics and update shift control information from sensors';

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
    {
        // Manejo de señales para una terminación controlada
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldContinue = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldContinue = false;
        });

        $this->shouldContinue = true;

        // Inicializar el cliente MQTT
        $mqtt = $this->initializeMqttClient(env('MQTT_SERVER'), intval(env('MQTT_PORT')));

        // Suscribirse a los tópicos
        $this->subscribeToAllTopics($mqtt);

        // Bucle principal para verificar y suscribirse a nuevos tópicos
        while ($this->shouldContinue) {
            // Verificar y suscribir a nuevos tópicos
            $this->checkAndSubscribeNewTopics($mqtt);

            // Mantener la conexión activa y procesar mensajes MQTT
            $mqtt->loop(true);

            // Permitir que el sistema maneje señales
            pcntl_signal_dispatch();

            // Reducir la carga del sistema esperando un corto período
            usleep(100000); // Esperar 0.1 segundos
        }

        // Desconectar el cliente MQTT de forma segura
        $mqtt->disconnect();
        $this->info("MQTT Subscriber stopped gracefully.");
    }


    private function initializeMqttClient($server, $port)
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME'));
        $connectionSettings->setPassword(env('MQTT_PASSWORD'));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true); // Limpia la sesión

        return $mqtt;
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        // Obtener los tópicos desde la tabla sensors
        $topics = Sensor::pluck('mqtt_topic_1')->toArray();

        foreach ($topics as $topic) {
            $topicWithShift = "{$topic}/shift"; // Añadir '/shift' al tópico

            if (!in_array($topicWithShift, $this->subscribedTopics)) {
                $mqtt->subscribe($topicWithShift, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topicWithShift;
                $this->info("Subscribed to topic: {$topicWithShift}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        // Obtener tópicos actuales desde sensors
        $currentTopics = Sensor::pluck('mqtt_topic_1')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            $topicWithShift = "{$topic}/shift"; // Añadir '/shift' al tópico

            if (!in_array($topicWithShift, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topicWithShift, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topicWithShift;
                $this->info("Subscribed to new topic: {$topicWithShift}");
            }
        }
    }

    private function processMessage($topic, $message)
    {
        $this->info("Processing message for topic: {$topic}");
    
        // Decodificar el mensaje JSON
        $data = json_decode($message, true);
    
        // Verificar si el JSON es válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Failed to decode JSON: " . json_last_error_msg());
            return;
        }
    
        $this->info("Message decoded: " . json_encode($data));
    
        // Buscar el sensor que coincide con el tópico (sin '/shift')
        $baseTopic = str_replace('/shift', '', $topic);
        $sensor = Sensor::where('mqtt_topic_1', $baseTopic)->first();
    
        if ($sensor) {
            $this->info("Sensor found for topic: {$baseTopic}");
    
            // Verificar si el JSON contiene shift_type y event
            if (isset($data['shift_type'])) {
                $sensor->shift_type = $data['shift_type'];
                $this->info("Shift type set to: {$data['shift_type']}");
            } else {
                $this->warn("Shift type missing in the message.");
            }
    
            if (isset($data['event'])) {
                $sensor->event = $data['event'];
                $this->info("Event set to: {$data['event']}");
            } else {
                $this->warn("Event missing in the message.");
            }
    
            // Reseteo de contadores del sensor
            $this->resetSensorCounters($sensor);
            
            // Anadir fecha a Oee con el ultimo cambio de turno por linia
            $this->changeDataTimeOee($sensor);

            $this->changeOrderStatus($sensor->production_line_id);

            // Guardar los cambios en el sensor
            $sensor->save();
            $this->info("Sensor ID {$sensor->id} updated with shift_type, event, and counters reset.");

             //mandar mqtt a 0
             $this->info("Intento enviar mqtt a 0 para el Sensor ID {$sensor->id} .");
             $this->sendMqttTo0($sensor);
    
        } else {
            $this->error("Sensor not found for topic: {$baseTopic}");
        }
    }

    // Función para resetear los contadores del sensor
    private function resetSensorCounters($sensor)
    {
        $this->info("Resetting counters for sensor ID {$sensor->id}.");

        // Reseteo de los contadores del sensor
        $sensor->count_shift_1 = 0;
        $sensor->count_shift_0 = 0;
        $sensor->count_order_0 = 0;
        $sensor->count_order_1 = 0;
        $sensor->downtime_count = 0;
        $sensor->unic_code_order = uniqid(); // Generar un nuevo código único para el pedido

        // Guardar los cambios en el sensor
        $sensor->save();

    }

    private function changeDataTimeOee($sensor)
    {
        try {
            // Información sobre la actualización de la hora de inicio de OEE
            $this->info("Actualizando hora de OEE para la línea {$sensor->production_line_id}.");

            // Obtener todos los registros de MonitorOee relacionados con la línea de producción
            $oees = MonitorOee::where('production_line_id', $sensor->production_line_id)->get(); // Cargar los modelos

            if ($oees->isNotEmpty()) {
                foreach ($oees as $oee) {
                    $oee->time_start_shift = Carbon::now();
                    $oee->save();  // Esto disparará el evento 'updating'
                }

                $this->info("Hora de inicio del turno actualizada para todos los monitores en la línea de producción {$sensor->production_line_id}.");
            } else {
                $this->warn("No se encontraron monitores para la línea de producción {$sensor->production_line_id}.");
            }


        } catch (\Exception $e) {
            // Capturar cualquier excepción y mostrar un mensaje de error
            $this->error("Ocurrió un error al actualizar la hora de OEE para la línea {$sensor->production_line_id}: " . $e->getMessage());
        }
    }

    private function sendMqttTo0($sensor){
        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => 0,
            'status' => 2
        ]);

        // Publicar el mensaje a través de MQTT
        $topic=$sensor->mqtt_topic_1 ;

        // Eliminar la parte '/mac/...' del tópico
        $topicWithoutMac = preg_replace('/\/mac\/[^\/]+/', '', $topic);

        // Añadir '/waitTime' al final del tópico
        $topicWaitTime = $topicWithoutMac . '/waitTime';
        $topicWaitTime2 = $topic . '/waitTime';
        $this->publishMqttMessage($topic, $processedMessage);
        $this->info("Resetting mqtt Counter to 0 for sensor ID {$sensor->id}.");
        $this->publishMqttMessage($topicWaitTime, $processedMessage);
        $this->info("Resetting mqtt counters waiTime to 0 for sensor ID {$sensor->id}.");
        $this->publishMqttMessage($topicWaitTime2, $processedMessage);
        $this->info("Resetting mqtt counters waiTime to 0 for sensor ID {$sensor->id}.");
    }

    private function changeOrderStatus($productionLineId)
    {
        // Buscar la última entrada en 'order_stats' con el mismo 'production_line_id'
        $lastOrderStat = OrderStat::where('production_line_id', $productionLineId)->latest()->first();
        
        $createNewEntry = false; // Indicador para saber si necesitamos crear una nueva entrada
    
        if ($lastOrderStat) {
            // Verificar si el registro se creó hace más de 1 minuto
            $createdAt = Carbon::parse($lastOrderStat->created_at);
            $oneMinuteAgo = Carbon::now()->subMinute();
    
            // Si el registro tiene más de 1 minuto de antigüedad, establecemos que se debe crear una nueva entrada
            if ($createdAt->lessThan($oneMinuteAgo)) {
                $createNewEntry = true;
            }
        } else {
            // Si no hay ningún registro anterior, necesitamos crear una nueva entrada
            $createNewEntry = true;
        }
    
        // Si se determina que se debe crear una nueva entrada
        if ($createNewEntry) {
            // Calcular las unidades restantes (units - units_made_real)
            $unitsRemaining = $lastOrderStat ? $lastOrderStat->units - $lastOrderStat->units_made_real : 0;
    
            // Crear una nueva línea con los campos necesarios y los demás vacíos o en 0
            OrderStat::create([
                'production_line_id' => $productionLineId,
                'order_id' => $lastOrderStat ? $lastOrderStat->order_id : null,
                'box' => $lastOrderStat ? $lastOrderStat->box : null,
                'units_box' => $lastOrderStat ? $lastOrderStat->units_box : null,
                'units' => $unitsRemaining,  // Aquí asignamos lo que queda por fabricar
                'units_per_minute_real' => null,  // Dejar estos campos vacíos o nulos
                'units_per_minute_theoretical' => null,
                'seconds_per_unit_real' => null,
                'seconds_per_unit_theoretical' => null,
                'units_made_real' => 0,
                'units_made_theoretical' => 0,
                'sensor_stops_count' => 0,
                'sensor_stops_active' => 0,
                'sensor_stops_time' => 0,
                'production_stops_time' => 0,
                'units_made' => 0,
                'units_pending' => 0,
                'units_delayed' => 0,
                'slow_time' => 0,
                'oee' => null,  // Dejar vacío o nulo
            ]);
    
            Log::info("Nueva entrada creada en order_stats para la línea de producción: {$productionLineId}");
        } else {
            Log::info("No se creó nueva entrada en order_stats ya que el último registro es reciente para la línea de producción: {$productionLineId}");
        }
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
