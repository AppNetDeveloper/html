<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\Sensor;
use App\Models\Modbus;

class MqttSubscriberLocal extends Command
{
    protected $signature = 'mqtt:subscribe-local';
    protected $description = 'Subscribe to MQTT topics and update order notices';

    protected $subscribedTopics = [];

    public function handle()
    {
        $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
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

        //$mqtt = new MqttClient($server, $port, uniqid());
        $mqtt = new MqttClient($server, $port, "order-notices-externo");
        $mqtt->connect($connectionSettings, true); // Limpia la sesión

        return $mqtt;
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        $topics = Barcode::pluck('mqtt_topic_barcodes')->map(function ($topic) {
            return $topic . "/prod_order_notice";
        })->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
       // $currentTopics = Barcode::pluck('mqtt_topic_orders')->toArray();
       $currentTopics = Barcode::pluck('mqtt_topic_barcodes')->map(function ($topic) {
            return $topic . "/prod_order_notice";
        })->toArray();
        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topic, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to new topic: {$topic}");
            }
        }
    }

    private function processMessage($topic, $message)
    {
        // Eliminar el sufijo "/prod_order_notice" del tópico
        $originalTopic = str_replace('/prod_order_notice', '', $topic);
    
        // Buscar el código de barras usando el tópico original
        $barcode = Barcode::where('mqtt_topic_orders', $originalTopic)->first();
    
        if ($barcode) {
            // Actualizar el aviso de orden para el código de barras encontrado
            $barcode->order_notice = $message;
            $barcode->save();
            $this->info("Aviso de pedido actualizado para código de barras {$barcode->id}");
    
            // Resetear los valores de sensores y modbuses
            $this->resetSensors($barcode->id, $topic);
            $this->resetModbuses($barcode->id, $topic);
        } else {
            $this->error("Barcode not found for topic: {$topic}");
        }
    }
    

    private function resetSensors($barcodeId, $topic)
    {
        Sensor::where('barcoder_id', $barcodeId)->chunk(100, function ($sensors) use ($topic) {
            foreach ($sensors as $sensor) {
                $sensor->count_order_0 = 0;
                $sensor->count_order_1 = 0;
                $sensor->save();
                $this->info("Reset count_order_0 and count_order_1 for sensor with id {$sensor->id}");
            }
        });

        if (Sensor::where('barcoder_id', $barcodeId)->count() == 0) {
            $this->error("Sensor not found for topic: {$topic}");
        }
    }

    private function resetModbuses($barcodeId, $topic)
    {
        Modbus::where('barcoder_id', $barcodeId)->chunk(100, function ($modbuses) use ($topic) {
            foreach ($modbuses as $modbus) {
                $modbus->rec_box = 0;
                $modbus->save();
                $this->info("Reset rec_box for modbus with id {$modbus->id}");
            }
        });

        if (Modbus::where('barcoder_id', $barcodeId)->count() == 0) {
            $this->error("Modbus not found for topic: {$topic}");
        }
    }
}
