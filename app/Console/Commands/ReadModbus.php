<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modbus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Cache;
use App\Models\ControlWeight;
use App\Models\ApiQueuePrint;
use Rawilk\Printing\Facades\Printing;
use Picqer\Barcode\BarcodeGeneratorException;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Models\ControlHeight;
use App\Models\Printer;
use App\Helpers\MqttHelper; // Importa el helper
use App\Helpers\MqttPersistentHelper;
use App\Models\LiveTrafficMonitor;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;


class ReadModbus extends Command
{
    protected $signature = 'modbus:read';
    protected $description = 'Read data from Modbus API and publish to MQTT';

    protected $mqttService;

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
{
    pcntl_async_signals(true);

    // Manejar señales para una terminación controlada
    pcntl_signal(SIGTERM, function () {
        $this->shouldContinue = false;
    });

    pcntl_signal(SIGINT, function () {
        $this->shouldContinue = false;
    });

    $this->shouldContinue = true;

    $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
    $this->subscribeToAllTopics($mqtt);

    // Bucle principal para verificar y suscribirse a nuevos tópicos
    while ($this->shouldContinue) {
        // Verificar y suscribir a nuevos tópicos
        $this->checkAndSubscribeNewTopics($mqtt);

        // Mantener la conexión activa y procesar mensajes MQTT
        $mqtt->loop(true);

        // Permitir que el sistema maneje señales
        pcntl_signal_dispatch();

        // Reducir la carga del sistema esperando un corto período
        usleep(100000); // Esperar 0.1 segundos
    }

    // Desconectar el cliente MQTT de forma segura
    $mqtt->disconnect();
    $this->info("MQTT Subscriber stopped gracefully.");
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
        $topics = Modbus::whereNotNull('mqtt_topic_modbus')
            ->where('mqtt_topic_modbus', '!=', '')
            ->pluck('mqtt_topic_modbus')
            ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    //sacamos el id para identificar la linia, pero solo id para no cargar la ram
                    $id = Modbus::where('mqtt_topic_modbus', $topic)->value('id');
                    //llamamos a procesar el mesaje
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
        $currentTopics = Modbus::pluck('mqtt_topic_modbus')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topic, function ($topic, $message) {
                    //sacamos id para identificar linia pero solo is para no cargar la ram
                    $id = Modbus::where('mqtt_topic_modbus', $topic)->value('id');
                    //llamamos a procesar el mesaje

                    $this->processMessage($id, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to new topic: {$topic}");
            }
        }
    }

    private function processMessage($id, $message)
    {

        $config = Modbus::where('id', $id)->first();

        $data = json_decode($message, true);
        if (is_null($data)) {
            Log::error("Error: El mensaje recibido no es un JSON válido.");
            return;
        }

        // Verificar si la configuración no existe
        if (is_null($config)) {
            Log::error("Error: No se encontró la configuración para line ID {$id}. La línea puede haber sido eliminada.");
            return;
        }

        $this->info("Contenido del line ID {$id} JSON: " . print_r($data, true));

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

        $this->info("Mensaje: {$config->name} (ID: {$config->id}) // topic: {$config->topic} // value: {$value}");
        //procesor modelo de sensor
        $this->processModel($config, $value, $data);
    }

    public function processModel($config, $value, $data)
    {
        switch ($config->model_name) {
            case 'weight':
                $this->processWeightModel($config, $value, $data);
                break;
            case 'height':
                $this->processHeightModel($config, $value, $data);
                break;
            case 'lifeTraficMonitor':
                $this->lifeTraficMonitor($config, $value);
                break;
            default:
                Log::warning("Modelo desconocido: {$config->model_name}");
                break;
        }
    }


    private function getValueFromJson($data, $jsonPath)
    {
        $keys = explode(', ', $jsonPath);
        foreach ($keys as $key) {
            $key = trim($key);
            if (isset($data[$key])) {
                return isset($data[$key]['value']) ? $data[$key]['value'] : null;
            }
        }
        return null;
    }

    public function processWeightModel($config, $value, $data)
    {
        $updatedValue = $value / $config->conversion_factor;
        
        
        if ($config->calibration_type == '0') { 
            // O 'software' si usas un booleano
            if ($updatedValue > $config->tara_calibrate) {
            // Restamos 'tara_calibrate' si es mayor
                $updatedValue -= $config->tara_calibrate;
            } 
                // Ahora, comparamos con 'tara' después de la posible resta anterior
            if ($updatedValue > $config->tara) {
                $updatedValue -= $config->tara;
            }
        } else { // Calibración por HARDWARE
            //Por momento no tengo logica de recalibrate por hRDWARE
        }
        
        $mqttTopic = $config->mqtt_topic . '1/gross_weight';
        $mqttTopic2 = $config->mqtt_topic . '2/gross_weight';
       // Obtiene el último valor guardado
        $lastValue = $config->last_value;
        $this->info("Mi valor:{$lastValue}");
        // Actualiza el valor en la base de datos si ha cambiado
        if ($updatedValue != $lastValue) {
            $updateResponse = $config->update(['last_value' => $updatedValue]);

            // Logea la respuesta de la actualización
            if ($updateResponse) {
                $this->info("Actualización exitosa. Valor original: {$lastValue}, Valor actualizado: {$updatedValue}");
            } else {
                Log::error("Error en la actualización de last_value. Valor original: {$lastValue}, Valor intentado actualizar: {$updatedValue}");
            }

            // Construye el mensaje
            $message = [
                'value' => $updatedValue,
                'time' => date('c')
            ];

            $this->info("Mensaje MQTT: " . json_encode($message));

            // Publica el mensaje MQTT
            $this->publishMqttMessage($mqttTopic, $message);
            //OJO CON ESTO ES SOLO SI LA BASCULA TIENE UN SOLO CONTADOR OJO
            $this->publishMqttMessage($mqttTopic2, $message);
        } else {
            // Logea que el valor no ha cambiado y no se envía el mensaje MQTT
            $this->info("Mismo valor no se manda MQTT: " . json_encode(['value' => $lastValue, 'time' => date('c')]));
        }
        $this->processWeightData($config, $updatedValue, $data);
    }

    // Implementar funciones para otros modelos
    public function processHeightModel($config, $value, $data)
    {
        // Lógica para procesar datos de altura
        $this->info("Procesando modelo de altura. Valor: {$value}");

        // Obtener valores relevantes de la configuración
        $dimensionDefault = $config->dimension_default;
        $dimensionMax = $config->dimension_max;
        $offsetMeter = $config->offset_meter;
        $dimensionVariation = $config->dimension_variacion;
        $dimensionOffset = $config->offset_meter;

        

        // Calcular el valor actual
        $currentValue = $dimensionDefault - $value + $offsetMeter;

        $this->info("Valor actual calculado: {$currentValue} y dimension maxima anterior : {$dimensionMax}");

        // Verificar si el valor actual es mayor que el máximo registrado
        if ($currentValue > $dimensionMax) {
            $this->info("Actualizando dimension_max: Valor actual {$currentValue} es mayor que dimension_max anterior {$dimensionMax}");
            $config->dimension_max = $currentValue;
            $config->save();

            $this->info("Nuevo dimension_max guardado en modbuses: {$currentValue}");

            // Actualizar dimension en otros registros de Modbuses donde dimension_id = $config->id
            Modbus::where('dimension_id', $config->id)
                    ->where('dimension', '<', $currentValue) // Verifica que el valor actual es mayor
                    ->where('max_kg', '!=', 0) // Verifica que max_kg no sea 0
                    ->update(['dimension' => $currentValue]);


        $this->info("dimension_max actualizado en otros registros de Modbuses donde dimension_id = {$config->id}");

        } else {
            $this->info("No se actualiza dimension_max: Valor actual {$currentValue} no es mayor que dimension_max {$dimensionMax}");
        }

        if (($value + $dimensionOffset) > ($dimensionDefault - $dimensionVariation) && $dimensionMax > ($dimensionOffset + $dimensionVariation)) {
             // Guardar el valor máximo actual antes de reiniciar
        $controlHeight = new ControlHeight();
        $controlHeight->modbus_id = $config->id;
        $controlHeight->height_value = $dimensionMax;
        $controlHeight->save();

        $this->info("Nuevo registro en control_heights guardado con dimension_max. Valor: {$dimensionMax}");

        // Reiniciar dimension_max a 0
        $config->dimension_max = 0;
        $config->save();
        $this->info("dimension_max reiniciado a 0 en modbuses.");

            $this->info("Nuevo registro en control_heights guardado con currentValue. Valor: {$currentValue}");
        }

    }

    public function lifeTraficMonitor($config, $value)
    {
        // Lógica para procesar datos del sensor
        $this->info("Monitor de trafico. Valor: {$value}");

        // Consultar el último valor guardado para este sensor
        $lastRecord = LiveTrafficMonitor::where('modbus_id', $config->id)
                                        ->orderBy('created_at', 'desc')
                                        ->first();

        // Comprobar si el nuevo valor es diferente al último valor registrado
        if ($lastRecord && $lastRecord->value == $value) {
            $this->info("El valor no ha cambiado. No se guarda el nuevo valor.");
            return;
        }

        // Si el valor es diferente o no hay registros previos, se guarda el nuevo valor
        $lifetraficMonitor = new LiveTrafficMonitor();
        $lifetraficMonitor->modbus_id = $config->id;
        $lifetraficMonitor->value = $value;
        $lifetraficMonitor->save();
    }


    private function processWeightData(Modbus $config, $value, $data)
    {
    // Obtener valores actuales de la base de datos
    $maxKg = floatval($config->max_kg);
    $totalKgOrder = floatval($config->total_kg_order);
    $totalKgShift = floatval($config->total_kg_shift);
    $repNumber = intval($config->rep_number);
    $minKg = floatval($config->min_kg);
    $lastKg = floatval($config->last_kg);
    $lastRep = intval($config->last_rep);
    $variacionNumber = floatval($config->variacion_number);
    $topic_control = $config->mqtt_topic . '1/control_weight';
    $topic_control2 = $config->mqtt_topic . '2/control_weight';
    $topic_box_control = $config->mqtt_topic . '1';
    $topic_box_control2 = $config->mqtt_topic . '2';
    $dimensionFinal = intval($config->dimension);
    //Log::debug("({$minKg} kg)");

    // Inicializar la variable para el número de cajas
    $newBoxNumber = intval($config->rec_box);
    $newBoxNumberShift = intval($config->rec_box_shift);
    $newBoxNumberUnlimited = intval($config->rec_box_unlimited);    
        // Lógica de control de peso y repeticiones
        if ($value >= $minKg) { // Si el valor actual es mayor o igual al mínimo
            $this->info("Valor actual ({$value} kg) es mayor o igual al mínimo ({$minKg} kg)"); // Logging detallado

            if (abs($value - $lastKg) <= $variacionNumber) { // Si la variación está dentro del rango permitido
                $this->info("Valor estable dentro del rango de variación.");
                $lastRep++; // Incrementar el contador de repeticiones

                if ($lastRep >= $repNumber && $value >= $minKg && $value > $maxKg) { // Si se alcanza el número de repeticiones requerido, pero el valor es mas grande que minimo permitido y que el valor es mas grande que maxKG
                    $this->info("Número de repeticiones alcanzado. Nuevo máximo: {$value} kg");
                    $maxKg = $value; // Actualizar el valor máximo
                    $lastRep = 0; // Reiniciar el contador de repeticiones
                }
            } else {
                $this->info("Valor fuera del rango de variación. Reiniciando repeticiones. El valor actual es:{$value} kg, el valor minimo: {$minKg} kg");
                $lastRep = 0; // Reiniciar el contador de repeticiones si la variación está fuera del rango permitido
            }

            $lastKg = $value; // Actualizar el último valor con el valor actual
        } elseif ($maxKg > $minKg && $value < $minKg) { // Si el valor es menor que el mínimo y $maxKg no es nulo
            $this->info("Valor por debajo del mínimo. Enviando mensaje de control de peso: {$maxKg} kg");



            // Verificar si el JSON tiene el campo 'check' y usarlo para asignar a maxKg
            if (isset($data['check'])) {
                $maxKg = $data['check'] /10;
                $this->info("Se ha obtenido el valor de 'check' desde el JSON: {$maxKg}");
            } else {
                $this->warning("No se encontró el campo 'check' en los datos recibidos.");
            }


            $messageControl = [
                        'type' => "NoEPC",
                        'unit' => "Kg",
                        'value' => $maxKg,
                        'excess' => "0",
                        'total_excess' => "0",
                        'rating' => "1",
                        'time' => date('c'),
                        'check' => "1",
                        'dimension' => $dimensionFinal
                ];
            $this->publishMqttMessage($topic_control, $messageControl); // Enviar mensaje de control
            $this->publishMqttMessage($topic_control2, $messageControl); // Enviar mensaje de control    

            // Incrementar el recuento de cajas en rec_box
            $newBoxNumber++; // es por orderId
            $newBoxNumberShift++; //por turno
            $newBoxNumberUnlimited++; //indefinido
            // Generar un número de barcoder único
            $uniqueBarcoder = uniqid('bar_', true);

                

            // Intentar guardar los datos en la tabla control_weight
        try {
            $controlWeight = ControlWeight::create([
                'modbus_id' => $config->id,
                'last_final_barcoder' => null,
            ]);

            // Log informativo de los datos guardados
            $this->info("Datos guardados en control_weight");
        } catch (\Exception $e) {
            // Log de errores al intentar guardar los datos
            $this->info("Error al guardar datos en control_weight");
        }

            $totalKgShift=$maxKg + $totalKgShift;
            $totalKgOrder= $maxKg + $totalKgOrder;
            $maxKg = 0; // Reiniciar el valor máximo
            $lastKg = 0; // Reiniciar el último valor
            $lastRep = 0; // Reiniciar el contador de repeticiones
            $dimensionFinal = 0; //Reiniciar altura de la caja palet


            //llamar mqtt recuento de bultos cajas
            $messageBoxNumber = [
                    'value' => $newBoxNumber,
                    'status' => 2
            ]; 
            $this->publishMqttMessage($topic_box_control, $messageBoxNumber); // Enviar mensaje de control

            //actualizr el peso acumulado por turno y order cuando se ha generado una nueva caja
                

            $messageTotalKgOrder = [
                'value' => round($totalKgOrder), // Redondea sin decimales
                'status' => 2
            ];
            
            $this->publishMqttMessage($topic_box_control2, $messageTotalKgOrder); // Enviar mensaje de control

                //llamar a la api externa si se ha pedido desde el cliente, esto comprueba si el cliente nos ha mandado valor en api para devolverle las info

            $apiQueue = ApiQueuePrint::where('modbus_id', $config->id)
                ->where('used', false)
                ->oldest()
                ->first();

            if ($apiQueue) {
                if ($apiQueue->value == 0) {
                    $apiQueue->used = true;
                    $apiQueue->save();
                } else {
                    $this->callExternalApi($apiQueue, $config, $newBoxNumber, $maxKg, $dimensionFinal, $uniqueBarcoder);
                }
            }

            //llamar a la impresora local para imprimir si es un bulto anonimo para habilitar bultos anonimos tenemos que anadir una impresora a la modbus si impresora no existe no se imprime, el printer_id tiene que no estar null con 0 o vacio
            if (!is_null($config->printer_id) && trim($config->printer_id)) {
                $this->printLabel($config, $uniqueBarcoder);
            } else {
                $this->info('No hay configuración para imprimir una etiqueta.');
            }
        }

        $config->update([
            'rec_box' => $newBoxNumber,
            'rec_box_shift' => $newBoxNumberShift,
            'rec_box_unlimited' => $newBoxNumberUnlimited,
            'max_kg' => $maxKg,
            'last_kg' => $lastKg,
            'last_rep' => $lastRep,
            'dimension' => $dimensionFinal,
            'total_kg_order' => $totalKgOrder,
            'total_kg_shift' => $totalKgShift
        ]);
    }
    private function printLabel($config, $uniqueBarcoder)
    {
        // Buscar la impresora en la base de datos (una sola vez)
        $printer = Printer::find($config->printer_id);

        if (!$printer) {
            // Manejo de caso donde la impresora no se encuentra
           // error_log('Impresora no encontrada con el ID: ' . $config->printer_id);
            return; // Salir de la función si no hay impresora
        }

        if ($printer->type == 0) { // Impresión local (CUPS)
            $generator = new BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($uniqueBarcoder, $generator::TYPE_CODE_128);

            // Convertir a Base64
            $base64Image = base64_encode($barcodeData);

            try {
                $printJob = Printing::newPrintTask()
                    ->printer($printer->name)
                    ->content($base64Image)
                    ->send();

                $this->info('Etiqueta impresa correctamente.');
            } catch (\Exception $e) {
                Log::error('Error al imprimir la etiqueta: ' . $e->getMessage());
                // Opcional: Mostrar mensaje de error al usuario
            }
        } else {
             // Impresión mediante API de Python
            $response = Http::post($printer->api_printer, [
                'barcode' => $uniqueBarcoder,
            ]);

            if ($response->failed()) {
               // error_log('Error al llamar a la API de Python: ' . $response->body());
            }
        }
    }


    private function callExternalApi($apiQueue, $config, $newBoxNumber, $maxKg, $dimensionFinal, $uniqueBarcoder)
    {
        //$this->info("Llamada a la API externa para el Modbus ID: {$config->id}");

        $dataToSend = [
            'token' => $apiQueue->token_back,
            'rec_box' => $newBoxNumber,
            'max_kg' => $maxKg,
            'last_dimension' => $dimensionFinal,
            'last_barcoder' => $uniqueBarcoder,
            'peso' => $maxKg,
            'alto' => $dimensionFinal,
            'used_value' => $apiQueue->value, 
        ];

        try {
            // Intenta POST primero
            $response = Http::post($apiQueue->url_back, $dataToSend);

            if (!$response->successful() && $response->status() === 405) { 
                // Si POST falla con 405, intenta PUT
                $response = Http::put($apiQueue->url_back, $dataToSend);
            }

            if ($response->successful()) {
                $this->info("Respuesta exitosa de la API externa para el Modbus ID: {$config->id}", [
                    'response' => $response->json(),
                ]);
            } else {
                Log::error("Error en la respuesta de la API externa para el Modbus ID: {$config->id}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error al llamar a la API externa para el Modbus ID: {$config->id}", [
                'error' => $e->getMessage(),
            ]);
        }

        $apiQueue->used = true;
        $apiQueue->save();

    // $apiQueue->delete();
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
