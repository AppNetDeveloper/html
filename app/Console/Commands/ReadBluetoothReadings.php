<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BluetoothAnt;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use GuzzleHttp\Client;

class ReadBluetoothReadings extends Command
{
    protected $signature = 'bluetooth:read';
    protected $description = 'Read data from Bluetooth API and publish to MQTT';

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () {
                $this->shouldContinue = false;
            });
            pcntl_signal(SIGINT, function () {
                $this->shouldContinue = false;
            });

            $this->shouldContinue = true;

            $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
            $this->subscribeToAllTopics($mqtt);

            while ($this->shouldContinue) {
                $mqtt->loop(true);
                pcntl_signal_dispatch();
                usleep(100000); // Esperar 0.1 segundos
            }

            $mqtt->disconnect();
            $this->info("MQTT Subscriber stopped gracefully.");

        } catch (\Exception $e) {
            $this->error("Error en el comando bluetooth:read: " . $e->getMessage());
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
        $antennas = BluetoothAnt::whereNotNull('mqtt_topic')
            ->where('mqtt_topic', '!=', '')
            ->get();

        foreach ($antennas as $antenna) {
            $topic = $antenna->mqtt_topic;

            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) use ($antenna) {
                    $this->processMessage($topic, $message, $antenna->name);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function processMessage($topic, $message)
    {
        $dataArray = json_decode($message, true);
    
        if (is_null($dataArray) || !is_array($dataArray)) {
            $this->error("Error: El mensaje recibido no es un JSON válido.");
            return;
        }
    
        // Capturamos solo `mac`, `rssi` y `change` desde el JSON
        $mac = $dataArray['mac'] ?? null;
        $rssi = $dataArray['rssi'] ?? null;
        $change = $dataArray['change'] ?? null;
    
        if (is_null($mac) || is_null($rssi) || is_null($change)) {
            $this->error("Error: Faltan datos en el JSON para uno de los objetos.");
            return;
        }
    
        // Buscamos el nombre de la antena usando el tópico
        $antenna = BluetoothAnt::where('mqtt_topic', $topic)->first();
        $antennaName = $antenna ? $antenna->name : null;
    
        if (is_null($antennaName)) {
            $this->error("Error: No se encontró una antena para el tópico {$topic}.");
            return;
        }
    
        // Enviar datos a la API con `mac`, `rssi`, `change`, y el nombre de la antena
        $this->sendToApi($mac, $rssi, $change, $antennaName);
    }
    
    
    private function sendToApi($mac, $rssi, $change, $antennaName)
    {
        $apiUrl = rtrim(env('APP_URL'), '/') . '/api/bluetooth/insert';
        $client = new Client([
            'timeout' => 3,
            'http_errors' => false,
            'verify' => false,
        ]);
    
        $dataToSend = [
            'mac' => $mac,
            'rssi' => $rssi,
            'change' => $change,
            'antenna_name' => $antennaName,
        ];
    
        try {
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend
            ]);
    
            $promise->then(
                function ($response) use ($mac) {
                    $this->info("API call success for MAC {$mac}: " . $response->getStatusCode());
                },
                function ($exception) use ($mac) {
                    $this->error("API call error for MAC {$mac}: " . $exception->getMessage());
                }
            );
    
            $promise->wait(false);
    
        } catch (\Exception $e) {
            $this->error("Error al intentar llamar a la API para MAC {$mac}: " . $e->getMessage());
        }
    
        $this->info("Mensaje procesado para MAC {$mac}, RSSI {$rssi}, Change {$change}");
    }
    
}
