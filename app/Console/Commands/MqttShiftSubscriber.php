<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShiftControl;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttShiftSubscriber extends Command
{
    protected $signature = 'mqtt:shiftsubscribe';
    protected $description = 'Subscribe to MQTT topics and update shift control information';

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
        // Obtener los tópicos desde la tabla shift_control
        $topics = ShiftControl::pluck('mqtt_topic')->toArray();

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
        // Obtener tópicos actuales desde shift_control
        $currentTopics = ShiftControl::pluck('mqtt_topic')->toArray();

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
        $this->info("Processing message for topic: {$topic}");
    
        // Decodificar el mensaje JSON
        $data = json_decode($message, true);
    
        // Verificar si el JSON es válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Failed to decode JSON: " . json_last_error_msg());
            return;
        }
    
        $this->info("Message decoded: " . json_encode($data));
    
        // Buscar el shift control que coincide con el tópico
        $shiftControl = ShiftControl::where('mqtt_topic', $topic)->first();
    
        if ($shiftControl) {
            $this->info("Shift control found for topic: {$topic}");
    
            // Verificar si el JSON contiene shift_type y event
            if (isset($data['shift_type'])) {
                $shiftControl->shift_type = $data['shift_type'];
                $this->info("Shift type set to: {$data['shift_type']}");
            } else {
                $this->warn("Shift type missing in the message.");
            }
    
            if (isset($data['event'])) {
                $shiftControl->event = $data['event'];
                $this->info("Event set to: {$data['event']}");
            } else {
                $this->warn("Event missing in the message.");
            }
    
            $shiftControl->save();
            $this->info("Shift control updated and saved.");
    
            // Obtener modbuses y sensors asociados a la línea de producción
            $productionLineId = $shiftControl->production_line_id;
            $this->info("Id linea de produccion: {$productionLineId}");
            $sensorId = $shiftControl->sensor_id;
            $this->info("Id de sensor: {$sensorId}");
            $modbusId = $shiftControl->modbus_id;
            $this->info("Id de modbus: {$modbusId}");
            

           // Verificar el valor del sensorId antes de continuar
            // Verificar la relación con Sensor
            $sensor = $shiftControl->sensor;

            if ($sensor) {
                $this->info("Sensor encontrado: {$sensor->name}, ID: {$sensor->id}");
                // Reseteo de contadores
                $sensor->count_shift_1 = 0;
                $sensor->count_shift_0 = 0;
                $sensor->count_order_0 = 0;
                $sensor->count_order_1 = 0;
                $sensor->downtime_count = 0;	
                $sensor->save();
                $this->info("Sensor ID {$sensor->id} reseteado.");
            } else {
                $this->warn("No se encontró el sensor asociado al shift control.");
            }

            // Verificar la relación con Modbus
            $modbus = $shiftControl->modbus;

            if ($modbus) {
                $this->info("Modbus encontrado: {$modbus->name}, ID: {$modbus->id}");
                // Reseteo de Modbus
                $modbus->rec_box_shift = 0;
                $modbus->rec_box = 0;
                $modbus->save();
                $this->info("Modbus ID {$modbus->id} reseteado.");
            } else {
                $this->warn("No se encontró el Modbus asociado al shift control.");
            }
    
            // Si sensor_id y modbus_id son null, pero production_line_id no lo es, reseteamos todos los sensores y modbuses asociados a la línea de producción
            if (is_null($sensorId) && is_null($modbusId) && !is_null($productionLineId)) {
                $this->info("Resetting all sensors and modbuses for production line ID {$productionLineId}.");

                // Obtener todos los sensores y modbuses asociados a la línea de producción
                $sensors = Sensor::where('production_line_id', $productionLineId)->get();
                $modbuses = Modbus::where('production_line_id', $productionLineId)->get();

                // Verificar cuántos sensores y modbuses fueron encontrados
                $this->info("Found " . $sensors->count() . " sensors and " . $modbuses->count() . " modbuses for production line ID {$productionLineId}.");

                // Código para resetear todos los sensores
                foreach ($sensors as $sensor) {
                    $sensor->count_shift_1 = 0;
                    $sensor->count_shift_0 = 0;
                    $sensor->count_order_0 = 0;
                    $sensor->count_order_1 = 0;
                    $sensor->save();
                    $this->info("Sensor ID {$sensor->id} reset.");
                }

                // Código para resetear todos los modbuses
                foreach ($modbuses as $modbus) {
                    $modbus->rec_box_shift = 0;
                    $modbus->rec_box = 0;
                    $modbus->save();
                    $this->info("Modbus ID {$modbus->id} reset.");
                }
            } else {
                $this->warn("No production line ID provided or sensor/modbus IDs are not null.");
            }

        } else {
            $this->error("Shift control not found for topic: {$topic}");
        }
    }     
}
