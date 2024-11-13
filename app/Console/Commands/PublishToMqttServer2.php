<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MqttSendServer2;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConnectionException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Log;

class PublishToMqttServer2 extends Command
{
    protected $signature = 'mqtt:publish-server2';
    protected $description = 'Publishes data to MQTT Server 2 based on mqtt_send_server2 table';

    protected $mqtt;
    private $maxReconnectAttempts = 5;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->initializeMqttClient();

        while (true) {
            try {
                $mqttEntries = MqttSendServer2::all();

                foreach ($mqttEntries as $entry) {
                    if ($this->publishToMqtt($entry)) {
                        $entry->delete(); // Elimina la entrada después de publicarla exitosamente
                    }
                }

                usleep(100000);

            } catch (DataTransferException $e) {
                $this->error("Data transfer error on MQTT Server 2: " . $e->getMessage());
                Log::error("Data transfer error on MQTT Server 2: " . $e->getMessage());
                
                // Desconecta y fuerza la reconexión
                $this->disconnectMqttClient();
                $this->reconnectClient();

            } catch (ConnectionException $e) {
                $this->error("Connection error in MQTT Server 2: " . $e->getMessage());
                Log::error("Connection error in MQTT Server 2: " . $e->getMessage());

                // Forzar la reconexión en caso de error de conexión
                $this->disconnectMqttClient();
                $this->reconnectClient();

            } catch (\Exception $e) {
                Log::error("Unexpected error in MQTT Server 2 publish loop: " . $e->getMessage());
                sleep(1);
            }
        }
    }

    private function initializeMqttClient()
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(20);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);

        $this->mqtt = new MqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')), uniqid());

        try {
            $this->mqtt->connect($connectionSettings, true);
            $this->info("Successfully connected to MQTT Server 2");
        } catch (ConnectionException $e) {
            $this->error("Connection error on MQTT Server 2: " . $e->getMessage());
        }
    }

    private function publishToMqtt($entry)
    {
        // Verifica si la conexión está activa antes de publicar
        if (!$this->mqtt->isConnected()) {
            $this->error("MQTT client is not connected. Attempting reconnection...");
            $this->reconnectClient();
        }

        try {
            $this->mqtt->publish($entry->topic, $entry->json_data, 0);
            $this->info("Successfully published to MQTT Server 2: " . $entry->topic);
            return true;
        } catch (DataTransferException $e) {
            $this->error("Data transfer error on MQTT Server 2: " . $e->getMessage());
            $this->disconnectMqttClient();
            return false;
        }
    }

    private function reconnectClient()
    {
        $attempts = 0;
        while ($attempts < $this->maxReconnectAttempts) {
            try {
                $this->initializeMqttClient();
                $this->info("Successfully reconnected to MQTT Server 2");
                return;
            } catch (\Exception $e) {
                $attempts++;
                Log::error("Reconnection attempt {$attempts} failed: " . $e->getMessage());
                sleep(2);
            }
        }

        Log::error("Max reconnection attempts reached. Exiting for Supervisor restart.");
        exit(1); // Permite que Supervisor lo reinicie
    }

    private function disconnectMqttClient()
    {
        if ($this->mqtt && $this->mqtt->isConnected()) {
            try {
                $this->mqtt->disconnect();
                $this->info("Disconnected from MQTT Server 2");
            } catch (\Exception $e) {
                $this->error("Error while disconnecting MQTT client: " . $e->getMessage());
                exit(1); // Forzar salida para que Supervisor reinicie
            }
        }
    }

    public function __destruct()
    {
        $this->disconnectMqttClient();
    }
}

