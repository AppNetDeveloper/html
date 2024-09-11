<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\Sensor;
use App\Models\Modbuse;

class MqttSubscriber extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT topics and update order notices';

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
        $topics = Barcode::pluck('mqtt_topic_orders')->toArray();

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
        $currentTopics = Barcode::pluck('mqtt_topic_orders')->toArray();

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
        $barcode = Barcode::where('mqtt_topic_orders', $topic)->first();

        if ($barcode) {
            // Actualizar el aviso de orden para el código de barras encontrado
            $barcode->order_notice = $message;
            $barcode->save();
            $this->info("Updated order notice for barcode {$barcode->id}");
            //resetear los valores a 0 en sensors

            // Resetear los valores a 0 en la tabla sensors
            $sensors = Sensor::where('barcoder_id', $barcode->id)->get();
            foreach ($sensors as $sensor) {
                $sensor->count_order_0 = 0;
                $sensor->count_order_1 = 0;
                $sensor->save();
                $this->info("Reset count_order_0 and count_order_1 for sensor with id {$sensor->id}");
            }

            // Resetear el campo rec_box a 0 en la tabla modbuses
            $modbuses = Modbus::where('barcoder_id', $barcode->id)->get();
            foreach ($modbuses as $modbus) {
                $modbus->rec_box = 0;
                $modbus->save();
                $this->info("Reset rec_box for modbus with id {$modbus->id}");
            }

        } else {
            $this->error("Barcode not found for topic: {$topic}");
        }
    }
}
