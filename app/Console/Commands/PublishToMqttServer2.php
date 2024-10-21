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

            } catch (\Exception $e) {
                $this->error("Error in MQTT Server 2 publish loop: " . $e->getMessage());
            }
        }
    }

    private function initializeMqttClient()
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);

        $this->mqtt = new MqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')), uniqid());

        try {
            $this->mqtt->connect($connectionSettings, true);

            $this->info("Successfully connected to MQTT Server 2");
        } catch (ConnectionException $e) {
            $this->error("Connection error on MQTT Server 2: " . $e->getMessage());
            $this->reconnectClient();
        }
    }

    private function publishToMqtt($entry)
    {
        try {
            $this->mqtt->publish($entry->topic, $entry->json_data, 0);
            $this->info("Successfully published to MQTT Server 2: " . $entry->topic);
            return true;
        } catch (DataTransferException $e) {
            $this->error("Data transfer error on MQTT Server 2: " . $e->getMessage());
            $this->reconnectClient();
            return false;
        }
    }

    private function reconnectClient()
    {
        try {
            $this->initializeMqttClient();
            $this->info("Successfully reconnected to MQTT Server 2");
        } catch (\Exception $e) {
            $this->error("Failed to reconnect to MQTT Server 2: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        if ($this->mqtt) {
            $this->mqtt->disconnect();
        }
    }
}
