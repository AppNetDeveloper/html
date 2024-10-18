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
        while (true) {
            try {
                $mqttEntries = MqttSendServer1::all();

                foreach ($mqttEntries as $entry) {
                    if ($this->initializeMqttClient()) {
                        if ($this->publishToMqtt($entry)) {
                            $entry->delete(); // Elimina la entrada después de publicarla exitosamente
                        }
                        $this->disconnectMqttClient();
                    }
                }

                usleep(100000);

            } catch (\Exception $e) {
                Log::error("Error in MQTT Server 1 publish loop: " . $e->getMessage());
            }
        }
    }

    private function initializeMqttClient()
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);

        $this->mqtt = new MqttClient(env('MQTT_SERVER'), intval(env('MQTT_PORT')), uniqid());

        try {
            $this->mqtt->connect($connectionSettings, true);
            return true;
        } catch (ConnectionException $e) {
            $this->error("Connection error on MQTT Server 1: " . $e->getMessage());
            $this->reconnectClient();
            return false;
        }
    }

    private function publishToMqtt($entry)
    {
        try {
            $this->mqtt->publish($entry->topic, $entry->json_data, 0);
            $this->info("Mesaje enviado a MQTT Server 1 topico: " . $entry->topic . " json_data: " . $entry->json_data . " Servidor: " . env('MQTT_SERVER') . " port: " . env('MQTT_PORT'));
            return true;
        } catch (DataTransferException $e) {
            $this->error("Data transfer error on MQTT Server 1: " . $e->getMessage());
            $this->reconnectClient();
            return false;
        }
    }

    private function disconnectMqttClient()
    {
        if ($this->mqtt) {
            try {
                $this->mqtt->disconnect();
                $this->info("Servidor desconectado por petición servidor1");
            } catch (\Exception $e) {
                $this->error("Error al desconectar el cliente MQTT: " . $e->getMessage());
            }
        }
    }

    private function reconnectClient()
    {
        try {
            $this->initializeMqttClient();
            $this->info("Successfully reconnected to MQTT Server 1");
        } catch (\Exception $e) {
            Log::error("Failed to reconnect to MQTT Server 1: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->disconnectMqttClient();
    }
}
