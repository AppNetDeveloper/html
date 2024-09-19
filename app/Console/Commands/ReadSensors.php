<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Cache;
//use App\Helpers\MqttPersistentHelper;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\Barcode;
use App\Models\SensorCount;

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

    public function handle()
    {
        try {
            // Inicializar el cliente MQTT
            $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
            
            // Suscribirse a los tópicos
            $this->subscribeToAllTopics($mqtt);

            // Bucle principal para verificar y suscribirse a nuevos tópicos
            while (true) {
                $this->checkAndSubscribeNewTopics($mqtt);
                $mqtt->loop(true); // Mantener la conexión activa y procesar mensajes

                // Permitir que Laravel maneje eventos internos mientras esperamos nuevos mensajes
                usleep(100000); // Esperar 0.1 segundos
            }

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

        // Verificar si la configuración no existe
        if (is_null($config)) {
            Log::error("Error: No se encontró la configuración para Sensor ID {$id}. El sensor puede haber sido eliminado.");
            return;
        }

        Log::info("Contenido del Sensor ID {$id} JSON: " . print_r($data, true));

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
        //invertir sensor si esta seleccionado
        $inversSensor = $config->invers_sensors;
        if ($inversSensor === true || $inversSensor === 1 || $inversSensor === "1") {
            $value = 1 - $value; // Invierte entre 0 y 1
        }
        
        Log::info("Mensaje: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}");
        // Procesar modelo de sensor
        $this->processModel($config, $value);
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


    private function processModel($config, $value)
    {
        // Recargar el $config desde la base de datos para obtener los valores más actualizados
        $config = Sensor::find($config->id);

        if (!$config) {
            $message = "Sensor no encontrado: ID {$config->id}";
            $this->info($message); // Muestra el mensaje en la consola
            Log::warning($message); // Guarda el mensaje en los logs
            return;
        }
        // Implementar la lógica específica del procesamiento basado en el modelo de sensor
        switch ($value) {
            case '0':
                $this->process0Model($config, $value);
                break;
            case '1':
                $this->process1Model($config, $value);
                break;
            default:
            $message = "Valo no permitido: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}";
            $this->info($message); // Muestra el mensaje en la consola
            Log::info($message); // Guarda el mensaje en los logs
                break;
        }
    }

    private function process0Model($config, $value)
    {
        $message = "Modelo 0: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}";
        $this->info($message); // Muestra el mensaje en la consola
        Log::info($message); // Guarda el mensaje en los logs
    
        // Obtener el registro del barcode asociado
        $barcode = Barcode::find($config->barcoder_id);
        
        if (!$barcode || !$barcode->order_notice) {
            $this->info("No se encontró el registro del barcode o el campo order_notice está vacío.");
            Log::warning("No se encontró el registro del barcode o el campo order_notice está vacío.");
            return;
        }
    
        // Decodificar el JSON almacenado en order_notice
        $orderNotice = json_decode($barcode->order_notice, true);
        
        // Extraer valores específicos del JSON
        $orderId = $orderNotice['orderId'] ?? null;
        $valuePerPackage = $orderNotice['refer']['value'] ?? null;
        $productName = $orderNotice['refer']['groupLevel'][0]['id'] ?? null;
        $unitsPerBox = $orderNotice['refer']['groupLevel'][0]['uds'] ?? null;
        $totalPerBox = $orderNotice['refer']['groupLevel'][0]['total'] ?? null;
    
        // Verificar valores extraídos
        $debugMessage = "Valores extraídos del JSON: OrderId: $orderId, ValuePerPackage: $valuePerPackage, ProductName: $productName";
        $this->info($debugMessage);
        Log::debug($debugMessage);
        
         // Intentar incrementar los contadores
        try {
            $config->increment('count_shift_0');
            $config->increment('count_total_0');
            $config->increment('count_order_0');
            $this->info("Contadores incrementados correctamente.");
            Log::info("Contadores incrementados correctamente.");
        } catch (\Exception $e) {
            $errorMessage = "Error al incrementar los contadores: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage);
            return;
        }
    
        // Calcular time_00 y time_10
        try {
            $previousEntry = SensorCount::where('sensor_id', $config->id)
                ->where('value', 0)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $time_00 = $previousEntry ? now()->diffInSeconds($previousEntry->created_at) : 300;
    
            $previousEntry1 = SensorCount::where('sensor_id', $config->id)
                        ->where('value', 1)
                        ->orderBy('created_at', 'desc')
                        ->first();
            $time_10 = $previousEntry1 ? now()->diffInSeconds($previousEntry1->created_at) : 300;
    
            $calculatedTimesMessage = "Valores calculados: time_00: $time_00, time_10: $time_10";
            $this->info($calculatedTimesMessage);
            Log::debug($calculatedTimesMessage);
        } catch (\Exception $e) {
            $errorMessage = "Error al calcular los tiempos: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage);
            return;
        }
    
        // Insertar el nuevo registro en la tabla sensor_counts
        try {
            SensorCount::create([
                'name' => $config->name,
                'value' => $value,
                'sensor_id' => $config->id, // Asegúrate de que este campo esté presente
                'production_line_id' => $config->production_line_id,
                'model_product' => $productName,
                'orderId' => $orderId,
                'count_total_0' => $config->count_total_0, // Ya incrementado arriba
                'count_shift_0' => $config->count_shift_0, // Ya incrementado arriba
                'count_order_0' => $config->count_order_0, // Ya incrementado arriba
                'time_00' => $time_00,
                'time_10' => $time_10,
            ]);
    
            $successMessage = "Registro insertado en sensor_counts correctamente.";
            $this->info($successMessage);
            Log::info($successMessage);
        } catch (\Exception $e) {
            $errorMessage = "Error al insertar en sensor_counts: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage);
        }
    
        // Determinar la función a ejecutar basada en function_model_0
        switch ($config->function_model_0) {
            case 'none':
                $this->none($config, $value);
                break;
            case 'sendMqttValue0':
                $this->sendMqttValue0($config, $value);
                break;
            default:
                $this->info("Función desconocida: {$config->function_model_0}");
                Log::warning("Función desconocida: {$config->function_model_0}");
                break;
        }
    }
    

    private function process1Model($config, $value)
    {
        $message = "Modelo 1: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}";
        $this->info($message); // Muestra el mensaje en la consola
        Log::info($message); // Guarda el mensaje en los logs

        // Obtener el registro del barcode asociado
        $barcode = Barcode::find($config->barcoder_id);
        
        if (!$barcode || !$barcode->order_notice) {
            $this->info("No se encontró el registro del barcode o el campo order_notice está vacío.");
            Log::warning("No se encontró el registro del barcode o el campo order_notice está vacío.");
            // Extraer valores específicos del JSON
            $orderId = "0";
            $valuePerPackage = "0";
            $productName = "0";
            $unitsPerBox = "0";
            $totalPerBox = "0";
        }else{
            // Decodificar el JSON almacenado en order_notice
            $orderNotice = json_decode($barcode->order_notice, true);
            
            // Extraer valores específicos del JSON
            $orderId = $orderNotice['orderId'] ?? null;
            $valuePerPackage = $orderNotice['refer']['value'] ?? null;
            $productName = $orderNotice['refer']['groupLevel'][0]['id'] ?? null;
            $unitsPerBox = $orderNotice['refer']['groupLevel'][0]['uds'] ?? null;
            $totalPerBox = $orderNotice['refer']['groupLevel'][0]['total'] ?? null;

            // Verificar valores extraídos
            $debugMessage = "Valores extraídos del JSON: OrderId: $orderId, ValuePerPackage: $valuePerPackage, ProductName: $productName";
            $this->info($debugMessage);
            Log::debug($debugMessage);
        }

        
        

         // Intentar incrementar los contadores
        try {
            $config->increment('count_shift_1');
            $config->increment('count_total_1');
            $config->increment('count_order_1');
            $this->info("Contadores incrementados correctamente.");
            Log::info("Contadores incrementados correctamente.");
        } catch (\Exception $e) {
            $errorMessage = "Error al incrementar los contadores: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage);
            return;
        }

        // Calcular time_11 y time_01
        try {
            $previousEntry = SensorCount::where('sensor_id', $config->id)
            ->where('value', 1)
            ->orderBy('created_at', 'desc')
            ->first();

            $time_11 = $previousEntry ? now()->diffInSeconds($previousEntry->created_at) : 300;

            $previousEntry0 = SensorCount::where('sensor_id', $config->id)
                    ->where('value', 0)
                    ->orderBy('created_at', 'desc')
                    ->first();
            $time_01 = $previousEntry0 ? now()->diffInSeconds($previousEntry0->created_at) : 300;

            $calculatedTimesMessage = "Valores calculados: time_11: $time_11, time_01: $time_01";
            $this->info($calculatedTimesMessage);
            Log::debug($calculatedTimesMessage);
        } catch (\Exception $e) {
            $errorMessage = "Error al calcular los tiempos: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage);
            return;
        }

        // Insertar el nuevo registro en la tabla sensor_counts
        try {
            SensorCount::create([
            'name' => $config->name,
            'value' => $value,
            'sensor_id' => $config->id, // Asegúrate de que este campo esté presente
            'production_line_id' => $config->production_line_id,
            'model_product' => $productName,
            'orderId' => $orderId,
            'count_total_1' => $config->count_total_1, // Ya incrementado arriba
            'count_shift_1' => $config->count_shift_1, // Ya incrementado arriba
            'count_order_1' => $config->count_order_1, // Ya incrementado arriba
            'time_11' => $time_11,
            'time_01' => $time_01,
            'unic_code_order'=>$config->unic_code_order,
            ]);

            $successMessage = "Registro insertado en sensor_counts correctamente.";
            $this->info($successMessage);
            Log::info($successMessage);
        } catch (\Exception $e) {
            $errorMessage = "Error al insertar en sensor_counts: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage);
        }

        

        // Determinar la función a ejecutar basada en function_model_1
        switch ($config->function_model_1) {
            case 'none':
                $this->none($config, $value);
                break;
            case 'sendMqttValue1':
                $this->sendMqttValue1($config, $value);
                break;
            default:
                $this->info("Función desconocida: {$config->function_model_1}");
                Log::warning("Función desconocida: {$config->function_model_1}");
                break;
        }
    }

    private function none($config, $value)
    {
        // No hace nada, función placeholder
    }

    private function sendMqttValue0($config, $value)
    {

        // Determinar el estado
        $status = 3; // Default a "sin datos"

        // Verificar que $lastTime sea un número válido
        if ($value === 1 || $value === "1") {
         
                $status = 2; // buen estado

            } else {
                $status = 0; // Parada
 
        }
        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => $config->count_order_0,
            'status' => $status,
        ]);

        $processedMessageTotal = json_encode([
            'value' => $config->count_total_0,
            'status' => $status,
        ]);

        $processedMessageTotalShift = json_encode([
            'value' => $config->count_shift_0,
            'status' => $status,
        ]);
        // Publicar el mensaje a través de MQTT
        
        $this->publishMqttMessage($config->mqtt_topic_1 . '/infinite_counter', $processedMessageTotal);
        $this->publishMqttMessage($config->mqtt_topic_1, $processedMessage);

        $this->info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1}");
        //Log::info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1}: {$processedMessage}");
    }

    private function sendMqttValue1($config, $value)
    {
        // Obtener el último valor de time_11 desde la tabla sensor_counts
        $lastTime = SensorCount::where('sensor_id', $config->id)
            ->where('value', 1)
            ->orderBy('created_at', 'desc')
            ->value('time_11');

        // Obtener los valores de tiempo óptimo y multiplicadores
        $optimalTime = $config->optimal_production_time;
        $reducedSpeedMultiplier = $config->reduced_speed_time_multiplier;

        // Determinar el estado
        $status = 3; // Default a "sin datos"

        // Verificar que $lastTime sea un número válido
        if (is_numeric($lastTime)) {
            if ($lastTime <= $optimalTime) {
                $status = 2; // Buen estado
            } elseif ($lastTime <= $optimalTime * $reducedSpeedMultiplier) {
                $status = 1; // Velocidad reducida
            } else {
                $status = 0; // Parada
            }
        }

        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => $config->count_order_1,
            'status' => $status,
        ]);

        $processedMessageTotal = json_encode([
            'value' => $config->count_total_1,
            'status' => $status,
        ]);

        $processedMessageTotalShift = json_encode([
            'value' => $config->count_shift_1,
            'status' => $status,
        ]);
        // Publicar el mensaje a través de MQTT
        
        $this->publishMqttMessage($config->mqtt_topic_1 . '/infinite_counter', $processedMessageTotal);
        $this->publishMqttMessage($config->mqtt_topic_1, $processedMessage);

        $this->info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1}");
        //Log::info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1}: {$processedMessage}");
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