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

class MqttShiftSubscriber extends Command
{
    protected $signature = 'mqtt:shiftsubscribe';
    protected $description = 'Subscribe to MQTT topics and update shift control information from sensors';

    protected $subscribedTopics = [];

    public function handle()
    {
        $mqtt = $this->initializeMqttClient(env('MQTT_SERVER'), intval(env('MQTT_PORT')));
        $this->subscribeToAllTopics($mqtt);

        // Bucle principal para verificar y suscribirse a nuevos tópicos
        while (true) {
            $this->checkAndSubscribeNewTopics($mqtt);
            $mqtt->loop(true); // Mantener la conexión activa y procesar mensajes

            // Permitir que Laravel maneje eventos internos mientras esperamos nuevos mensajes
            usleep(100000); // Esperar 0.1 segundos
        }
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
        $sensor->unic_code_order = uniqid();
    }

    private function changeDataTimeOee($sensor)
    {
        $this->info("Actualizar horra de Oee para el la linia {$sensor->production_line_id}.");

        $oee = MonitorOee::where('production_line_id', $sensor->production_line_id)->first();
        if ($oee) {
            $oee->time_start_shift = Carbon::now();
            $oee->save();
        }
    }
    private function sendMqttTo0($sensor){
        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => 0,
            'status' => 2,
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
