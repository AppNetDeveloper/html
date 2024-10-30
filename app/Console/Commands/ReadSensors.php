<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;


class ReadSensors extends Command
{
    protected $signature = 'sensors:read';
    protected $description = 'Read data from Sensors API and publish to MQTT';

    protected $mqttService;
    protected $subscribedTopics = [];

    public function __construct()
    {
        parent::__construct();
       // MqttPersistentHelper::init();
    }
    protected $shouldContinue = true;

    public function handle()
    {
        try {
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
            $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
            
            // Suscribirse a los tópicos
            $this->subscribeToAllTopics($mqtt);
    
            // Bucle principal para verificar y suscribirse a nuevos tópicos
            while ($this->shouldContinue) {
                // Verificar y suscribir a nuevos tópicos
               // $this->checkAndSubscribeNewTopics($mqtt);
    
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
    
        } catch (\Exception $e) {
            // Capturar cualquier excepción y registrarla en los logs
            Log::error("Error en el comando sensors:read: " . $e->getMessage());
            $this->error("Error en el comando sensors:read: " . $e->getMessage());
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
        $topics = Sensor::whereNotNull('mqtt_topic_sensor')
            ->where('mqtt_topic_sensor', '!=', '')
            ->pluck('mqtt_topic_sensor')
            ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    // Sacamos el id para identificar la línea, pero solo id para no cargar la RAM
                    $id = Sensor::where('mqtt_topic_sensor', $topic)->value('id');
                    // Llamamos a procesar el mensaje
                    $this->processMessage($id, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        $currentTopics = Sensor::pluck('mqtt_topic_sensor')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topic, function ($topic, $message) {
                    // Sacamos id para identificar la línea pero solo id para no cargar la RAM
                    $id = Sensor::where('mqtt_topic_sensor', $topic)->value('id');
                    // Llamamos a procesar el mensaje
                    $this->processMessage($id, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to new topic: {$topic}");
            }
        }
    }

    private function processMessage($id, $message)
    {
        $config = Sensor::where('id', $id)->first();
        $data = json_decode($message, true);
        
        if (is_null($data)) {
            Log::error("Error: El mensaje recibido no es un JSON válido.");
            return;
        }
    
        if (is_null($config)) {
            Log::error("Error: No se encontró la configuración para Sensor ID {$id}. El sensor puede haber sido eliminado.");
            return;
        }
    
        $this->info("Contenido del Sensor ID {$id} JSON: " . print_r($data, true));
    
        $value = null;
        if (empty($config->json_api)) {
            $value = $data['value'] ?? null;
            if ($value === null) {
                Log::error("Error: No se encontró 'value' en el JSON cuando json_api está vacío.");
                return;
            }
        } else {
            $jsonPath = $config->json_api;
            $value = $this->getValueFromJson($data, $jsonPath);
            if ($value === null) {
                Log::warning("Advertencia: No se encontró la clave '$jsonPath' en la respuesta JSON, buscando el valor directamente.");
                $value = $data['value'] ?? null;
                if ($value === null) {
                    Log::error("Error: No se encontró 'value' en el JSON.");
                    return;
                }
            }
        }

    
        // Llamada asíncrona a la API
        $appUrl = rtrim(env('APP_URL'), '/');
        $apiUrl = $appUrl . '/api/sensor-insert'; // Corregido: agregada la barra
    
        // Configurar cliente Guzzle para operaciones asíncronas
        $client = new \GuzzleHttp\Client([
            'timeout' => 0.1,
            'http_errors' => false,
            'verify' => false
        ]);
    
        // Datos para enviar
        $dataToSend = [
            'value' => $value,
            'sensor' => $config->mqtt_topic_sensor,
        ];
    
        try {
            // Crear la promesa
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend
            ]);
    
            // Manejar la promesa de forma no bloqueante
            $promise->then(
                function ($response) use ($config) {
                    $this->info("API call success for sensor {$config->id}: " . $response->getStatusCode());
                },
                function ($exception) use ($config) {
                    $this->error("API call error for sensor {$config->id}: " . $exception->getMessage());
                }
            );
    
            // Importante: Permitir que la promesa se resuelva en el siguiente ciclo del event loop
            $promise->wait(false);
    
        } catch (\Exception $e) {
            $this->error("Error al intentar llamar a la API: " . $e->getMessage());
        }
    
        $this->info("Mensaje procesado para sensor {$config->id} con valor {$value}");
    }


    private function getValueFromJson($data, $jsonPath)
    {
        
        $keys = explode(', ', $jsonPath);
        foreach ($keys as $key) {
            $key = trim($key);
            if (isset($data[$key])) {
                $value = isset($data[$key]['value']) ? $data[$key]['value'] : null;

                return $value;
            }
        }
        return null;
    }
}