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
    protected $shouldContinue = true;

    public function handle()
    {
        // Habilitar señales para poder detener el proceso limpiamente
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldContinue = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldContinue = false;
        });
    
        while ($this->shouldContinue) {
            try {
                $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
                $this->subscribeToAllTopics($mqtt);
    
                while ($this->shouldContinue) {
                    $this->checkAndSubscribeNewTopics($mqtt);
                    $mqtt->loop(true);
                    usleep(100000);
                }
    
                $mqtt->disconnect();
                $this->info("MQTT Subscriber stopped gracefully.");
    
            } catch (\Exception $e) {
                $this->error("Error connecting or processing MQTT client: " . $e->getMessage());
                // Esperar un poco antes de intentar reconectar
                sleep(5);
                $this->info("Reconnecting to MQTT...");
            }
        }
    }
    


    private function initializeMqttClient($server, $port)
    {
        $this->info("Subscribed en server: {$server} y port: {$port}");
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME',""));
        $connectionSettings->setPassword(env('MQTT_PASSWORD', ""));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true);

        return $mqtt;
    }

    private function subscribeToTopic(MqttClient $mqtt, string $topic)
    {
        if (!in_array($topic, $this->subscribedTopics)) {
            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->processMessage($topic, $message);
            }, 0);

            $this->subscribedTopics[] = $topic;
            $this->info("Subscribed to topic: {$topic}");
        }
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        $topics = Barcode::pluck('mqtt_topic_barcodes')->map(function ($topic) {
            return $topic . "/prod_order_notice";
        })->toArray();

        foreach ($topics as $topic) {
            $this->subscribeToTopic($mqtt, $topic);
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        $currentTopics = Barcode::pluck('mqtt_topic_barcodes')->map(function ($topic) {
            return $topic . "/prod_order_notice";
        })->toArray();

        foreach ($currentTopics as $topic) {
            $this->subscribeToTopic($mqtt, $topic);
        }
    }

    private function processMessage($topic, $message)
    {
        // Limpiar el mensaje JSON
        $cleanMessage = json_decode($message, true);  // Convertir el JSON a un array

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("El JSON proporcionado no es válido: " . json_last_error_msg());
            return;
        }


        // Convertir de nuevo a JSON y remover barras invertidas
        $cleanMessageJson = json_encode($cleanMessage, JSON_UNESCAPED_SLASHES);

        $originalTopic = str_replace('/prod_order_notice', '', $topic);
        $barcodes = Barcode::where('mqtt_topic_barcodes', $originalTopic)->get();
    
        if ($barcodes->isEmpty()) {
            $this->error("No barcodes found for topic: {$topic}");
            return;
        }else{
            $this->info("barcodes found for topic: {$topic}");
        }

    
        foreach ($barcodes as $barcode) {
            $this->info("Verificando barcode ID: {$barcode->id}, sended: {$barcode->sended}");
            
                // Guardar el aviso de pedido
                $barcode->order_notice = $cleanMessageJson;
                $barcode->sended = 0;  // Después de guardar, poner `sended` a 0
                try {
                    $barcode->save();
                    $this->info("Código de barras guardado correctamente: {$barcode->id}");
                } catch (\Exception $e) {
                    $this->error("Error al guardar el código de barras: {$e->getMessage()}");
                }
                
        
                // Resetear sensores y modbuses asociados
                $this->resetSensors($barcode->id);
                $this->resetModbuses($barcode->id);

        }
        
    }

    private function resetSensors($barcodeId)
    {
        try {
            $updated = Sensor::where('barcoder_id', $barcodeId)->update([
                'count_order_0' => 0,
                'count_order_1' => 0,
                'downtime_count'=> 0,
            ]);

            if ($updated > 0) {
                $this->info("Reset count_order_0 and count_order_1 for {$updated} sensors for barcode ID {$barcodeId}");
            } else {
                $this->error("Sensor not found for barcode ID: {$barcodeId}");
            }
        } catch (\Exception $e) {
            $this->error("Error updating sensors: " . $e->getMessage());
        }
    }


    private function resetModbuses($barcodeId)
    {
        try {
            $updated = Modbus::where('barcoder_id', $barcodeId)->update([
                'rec_box' => 0,
            ]);
    
            if ($updated > 0) {
                $this->info("Reset rec_box for {$updated} modbuses for barcode ID {$barcodeId}");
            } else {
                $this->error("Modbus not found for barcode ID: {$barcodeId}");
            }
    
        } catch (\Exception $e) {
            $this->error("Error updating Modbus: " . $e->getMessage());
        }
    }
    
}
