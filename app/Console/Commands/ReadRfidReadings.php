<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RfidAnt;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use GuzzleHttp\Client;

class ReadRfidReadings extends Command
{
    protected $signature = 'rfid:read';
    protected $description = 'Read data from RFID API and publish to MQTT';

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Configurar manejo de señales para una terminación controlada
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () {
                $this->shouldContinue = false;
            });
            pcntl_signal(SIGINT, function () {
                $this->shouldContinue = false;
            });

            $this->shouldContinue = true;

            // Inicializar el cliente MQTT
            $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
            
            // Suscribirse a los tópicos de RFID de cada antena
            $this->subscribeToAllTopics($mqtt);

            // Bucle principal para recibir mensajes y manejar suscripciones
            while ($this->shouldContinue) {
                $mqtt->loop(true);
                pcntl_signal_dispatch();
                usleep(100000); // Esperar 0.1 segundos para reducir la carga del sistema
            }

            $mqtt->disconnect();
            $this->info("MQTT Subscriber stopped gracefully.");

        } catch (\Exception $e) {
            $this->error("Error en el comando rfid:read: " . $e->getMessage());
        }
    }

    private function initializeMqttClient($server, $port)
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME'));
        $connectionSettings->setPassword(env('MQTT_PASSWORD'));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true);

        return $mqtt;
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        // Obtener todos los tópicos desde el modelo RfidAnt
        $antennas = RfidAnt::whereNotNull('mqtt_topic')
            ->where('mqtt_topic', '!=', '')
            ->get();

        foreach ($antennas as $antenna) {
            $topic = $antenna->mqtt_topic;

            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) use ($antenna) {
                    $this->processMessage($topic, $message, $antenna->name); // Pasar el name
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function processMessage($topic, $message, $antennaName)
    {
        $dataArray = json_decode($message, true);
    
        if (is_null($dataArray) || !is_array($dataArray)) {
            $this->error("Error: El mensaje recibido no es un JSON válido.");
            return;
        }

        foreach ($dataArray as $data) {
            $epc = $data['epc'] ?? null;
            $rssi = $data['rssi'] ?? null;
            $serialno = $data['serialno'] ?? null;
            $tid = $data['tid'] ?? null;
            $ant = $data['ant'] ?? null;
    
            if (is_null($epc) || is_null($rssi) || is_null($serialno) || is_null($tid)) {
                $this->error("Error: Faltan datos en el JSON para uno de los objetos.");
                continue;
            }
    
            // Enviar datos a la API
            $this->sendToApi($epc, $rssi, $serialno, $tid, $ant, $antennaName);
        }
    }
    
    private function sendToApi($epc, $rssi, $serialno, $tid, $ant, $antennaName)
    {
        $apiUrl = rtrim(env('APP_URL'), '/') . '/api/rfid-insert';
        $client = new Client([
            'timeout' => 0.1,
            'http_errors' => false,
            'verify' => false,
        ]);
    
        $dataToSend = [
            'epc' => $epc,
            'rssi' => $rssi,
            'serialno' => $serialno,
            'tid' => $tid,
            'ant' => $ant,
            'antenna_name' => $antennaName, // Incluye el ID de la antena
        ];
    
        try {
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend
            ]);
    
            $promise->then(
                function ($response) use ($epc) {
                    $this->info("API call success for EPC {$epc}: " . $response->getStatusCode());
                },
                function ($exception) use ($epc) {
                    $this->error("API call error for EPC {$epc}: " . $exception->getMessage());
                }
            );
    
            // Permitir que la promesa se resuelva en el siguiente ciclo del event loop
            $promise->wait(false);
    
        } catch (\Exception $e) {
            $this->error("Error al intentar llamar a la API para EPC {$epc}: " . $e->getMessage());
        }
    
        $this->info("Mensaje procesado para EPC {$epc}, RSSI {$rssi}, SerialNo {$serialno}, TID {$tid}, Antena: {$antennaName}");
    }
}
