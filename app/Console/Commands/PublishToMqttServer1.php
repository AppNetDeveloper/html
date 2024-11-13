<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MqttSendServer1;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConnectionException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Log;

class PublishToMqttServer1 extends Command
{
    protected $signature = 'mqtt:publish-server1';
    protected $description = 'Publishes data to MQTT Server 1 based on mqtt_send_server1 table';
    protected $mqtt; // Definición de la propiedad MQTT

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->initializeMqttClient()) { // Inicializar la conexión al inicio

            while (true) {
                try {
                    
                    $mqttEntries = MqttSendServer1::all();

                    foreach ($mqttEntries as $entry) {
                        if ($this->publishToMqtt($entry)) {
                            $entry->delete(); // Elimina la entrada después de publicarla exitosamente
                        }
                    }

                    usleep(100000); // Pausa para evitar sobrecarga

                } catch (\Exception $e) {
                    Log::error("Error in MQTT Server 1 publish loop: " . $e->getMessage());
                    $this->reconnectClient(); // Intentar reconectar si hay un error
                }
            }

            $this->disconnectMqttClient(); // Desconectar al final del proceso
        }
    }

    private function initializeMqttClient()
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(20); // Mantener viva la conexión
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);

        $this->mqtt = new MqttClient(env('MQTT_SERVER'), intval(env('MQTT_PORT')), uniqid());

        try {
            $this->mqtt->connect($connectionSettings, true);
            $this->info("Conectado a MQTT Server 1");
            return true;
        } catch (ConnectionException $e) {
            $this->error("Error de conexión con MQTT Server 1: " . $e->getMessage());
            return false;
        }
    }

    private function publishToMqtt($entry)
    {
        try {
            $this->mqtt->publish($entry->topic, $entry->json_data, 0); // Publicar con QoS 0
            $this->info("Mensaje enviado a MQTT Server 1 topico: " . $entry->topic . " json_data: " . $entry->json_data);
            return true;
        } catch (DataTransferException $e) {
            $this->error("Error de transferencia de datos en MQTT Server 1: " . $e->getMessage());
            exit(1);
            return false;
        }
    }

    private function disconnectMqttClient()
    {
        if ($this->mqtt) {
            try {
                $this->mqtt->disconnect();
                $this->info("Desconectado del servidor MQTT 1");
            } catch (\Exception $e) {
                $this->error("Error al desconectar el cliente MQTT: " . $e->getMessage());
            }
        }
    }

    private function reconnectClient()
    {
        try {
            $this->initializeMqttClient(); // Intentar reconectar si es necesario
            $this->info("Reconectado exitosamente a MQTT Server 1");
        } catch (\Exception $e) {
            Log::error("Fallo al reconectar con MQTT Server 1: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->disconnectMqttClient(); // Asegurar la desconexión al finalizar el script
    }
}
