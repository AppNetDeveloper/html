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
        // Buscar el shift control que coincide con el tópico
        $shiftControl = ShiftControl::where('mqtt_topic', $topic)->first();
    
        if ($shiftControl) {
            // Decodificar el mensaje JSON y actualizar shift_type y event
            $data = json_decode($message, true);
    
            // Verificar si el JSON contiene shift_type y event
            if (isset($data['shift_type'])) {
                $shiftControl->shift_type = $data['shift_type'];
            }
    
            if (isset($data['event'])) {
                $shiftControl->event = $data['event'];
            }
    
            $shiftControl->save();
            $this->info("Updated shift control for topic {$topic} with shift_type: {$data['shift_type']} and event: {$data['event']}");
    
            // Aquí procesamos la lógica de shift control según el shift_type y el event
    
            // Obtener todos los modbuses y sensors asociados a la misma línea de producción
            $modbuses = Modbus::where('production_line_id', $shiftControl->production_line_id)->get();
            $sensors = Sensor::where('production_line_id', $shiftControl->production_line_id)->get();
    
            // Procesar según el shift_type y event
            switch ($data['shift_type']) {
                case 'Turno Programado':
                    if ($data['event'] === 'start') {
                        // Resetear rec_box_shift en modbuses y count_shift_1, count_shift_0 en sensors
                        foreach ($modbuses as $modbus) {
                            $modbus->rec_box_shift = 0;
                            $modbus->save();
                        }
                        foreach ($sensors as $sensor) {
                            $sensor->count_shift_1 = 0;
                            $sensor->count_shift_0 = 0;
                            $sensor->save();
                        }
                        $this->info("Turno Programado started. Reset rec_box_shift in modbuses and count_shift in sensors.");
                    } elseif ($data['event'] === 'end') {
                        // Lógica al finalizar un Turno Programado
                        $this->info("Turno Programado ended.");
                    }
                    break;
    
                case 'Parada Programada':
                    if ($data['event'] === 'start') {
                        // Lógica para iniciar una Parada Programada
                        $this->info("Parada Programada started.");
                    } elseif ($data['event'] === 'end') {
                        // Lógica para finalizar una Parada Programada
                        $this->info("Parada Programada ended.");
                    }
                    break;
    
                case 'Parada NO Programada':
                    if ($data['event'] === 'start') {
                        // Lógica para iniciar una Parada NO Programada
                        $this->info("Parada NO Programada started.");
                    } elseif ($data['event'] === 'end') {
                        // Lógica para finalizar una Parada NO Programada
                        $this->info("Parada NO Programada ended.");
                    }
                    break;
    
                default:
                    $this->error("Unrecognized shift_type: {$data['shift_type']}");
                    break;
            }
    
        } else {
            $this->error("Shift control not found for topic: {$topic}");
        }
    }    
}
