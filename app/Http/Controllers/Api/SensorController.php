<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Log;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\Barcode;
use App\Models\SensorCount;

class SensorController extends Controller
{
        /**
     * @OA\Get(
     *     path="/api/sensors/{token}",
     *     summary="Obtener datos del sensor por token",
     *     tags={"Sensores"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token del sensor",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos del sensor obtenidos correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Sensor 1"),
     *             @OA\Property(property="sensor_type", type="integer", example=0),
     *             @OA\Property(property="mqtt_topic_sensor", type="string", example="sensor/topic/1"),
     *             @OA\Property(property="count_total", type="integer", example=100),
     *             @OA\Property(property="token", type="string", example="abc123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sensor no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Sensor not found")
     *         )
     *     )
     * )
     */
    public function getByToken($token)
    {
        // Buscar el sensor por su token
        $sensor = Sensor::where('token', $token)->first();

        // Verificar si el sensor existe
        if (!$sensor) {
            return response()->json([
                'error' => 'Sensor not found'
            ], 404);
        }

        // Buscar el último registro de sensor_counts para este sensor
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)->latest()->first();

        // Obtener el valor 'value', por defecto 0 si no se encuentra registro
        $value = $sensorCount ? $sensorCount->value : 0;

        // Devolver los datos del sensor en formato JSON, incluyendo el nuevo campo 'value'
        $sensorData = $sensor->toArray();
        $sensorData['value'] = $value;

        return response()->json($sensorData);
    }
    /**
     * @OA\Get(
     *     path="/api/sensors",
     *     summary="Obtener todos los sensores",
     *     description="Obtiene una lista de todos los sensores, agrupados por línea de producción.",
     *     tags={"Sensores"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de sensores",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sensor 1"),
     *                 @OA\Property(property="value", type="integer", example=0),
     *                 @OA\Property(property="production_line", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Línea 1")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllSensors()
    {
        // Obtener todos los sensores, cargando la relación con la línea de producción
        $sensors = Sensor::with('productionLine')->get();

        // Iterar sobre cada sensor para obtener el último valor de sensor_counts
        $sensorsData = $sensors->map(function ($sensor) {
            // Buscar el último registro de sensor_counts para este sensor
            $sensorCount = SensorCount::where('sensor_id', $sensor->id)->latest()->first();
            $value = $sensorCount ? $sensorCount->value : 0;

            // Añadir el campo 'value' a los datos del sensor
            $sensorData = $sensor->toArray();
            $sensorData['value'] = $value;

            return $sensorData;
        });

        // Agrupar los sensores por el nombre de la línea de producción
        $groupedSensors = $sensorsData->groupBy(function ($sensor) {
            return $sensor['production_line']['name']; // Agrupar por el nombre de la línea de producción
        });

        // Devolver la lista de sensores organizados por nombre de la línea de producción
        return response()->json($groupedSensors);
    }

    /**
     * @OA\Post(
     *     path="/api/sensors/insert",
     *     summary="Insertar datos del sensor",
     *     description="Inserta datos en el sensor, verificando si necesita invertir el valor según su configuración.",
     *     tags={"Sensores"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="integer", example=1),
     *             @OA\Property(property="sensor", type="string", example="sensor/topic")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos procesados correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Datos procesados correctamente"),
     *             @OA\Property(property="sensor", type="string", example="Sensor 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sensor no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Sensor no encontrado")
     *         )
     *     )
     * )
     */
    public function sensorInsert(Request $request)
    {
        // Validar que el cuerpo de la solicitud tenga los campos necesarios
        $validated = $request->validate([
            'value' => 'required|integer',
            'sensor' => 'required|string',
        ]);

        // Buscar el sensor usando el campo 'sensor'
        $topic = $request->input('sensor'); // Obtener el valor del campo 'sensor'
        $sensor = Sensor::where('mqtt_topic_sensor', $topic)->first();

        // Verificar si el sensor existe
        if (!$sensor) {
            return response()->json(['error' => 'Sensor no encontrado'], 404);
        }

        // Procesar el valor del sensor (lógica del proceso similar a la de MQTT)
        $value = $validated['value'];
        
        // Invertir sensor si está seleccionado
        $inversSensor = $sensor->invers_sensors;
        if ($inversSensor === true || $inversSensor === 1 || $inversSensor === "1") {
            $value = 1 - $value; // Invierte entre 0 y 1
        }

        $this->processModel($sensor, $value);

        // Retornar la respuesta
        return response()->json(['message' => 'Datos procesados correctamente', 'sensor' => $sensor->name], 200);
    }








    private function processModel($config, $value)
    {
         // Obtener el registro del barcode asociado
         $barcode = Barcode::find($config->barcoder_id);
        
         if (!$barcode || !$barcode->order_notice) {
             Log::warning("No se encontró el registro del barcode o el campo order_notice está vacío.");
             return;
         }

        // Implementar la lógica específica del procesamiento basado en el modelo de sensor
        switch ($value) {
            case '0':
                $this->process0Model($config, $value, $barcode);
                break;
            case '1':
                $this->process1Model($config, $value, $barcode);
                break;
            default:
            // Log::info("Valo no permitido: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}"); // Muestra el mensaje en la consola
                break;
        }
    }

    private function process0Model($config, $value, $barcode)
    {

        // Log::info("Modelo 0: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}"); // Muestra el mensaje en la consola

        // Decodificar el JSON almacenado en order_notice
        $orderNotice = json_decode($barcode->order_notice, true);
        
        // Extraer valores específicos del JSON
        $orderId = $orderNotice['orderId'] ?? null;
        $valuePerPackage = $orderNotice['refer']['value'] ?? null;
        $productName = $orderNotice['refer']['groupLevel'][0]['id'] ?? null;
        $unitsPerBox = $orderNotice['refer']['groupLevel'][0]['uds'] ?? null;
        $totalPerBox = $orderNotice['refer']['groupLevel'][0]['total'] ?? null;
    
        // Verificar valores extraídos
        //Log::info("Valores extraídos del JSON: OrderId: $orderId, ValuePerPackage: $valuePerPackage, ProductName: $productName");
        
         // Intentar incrementar los contadores
        try {
            $config->increment('count_shift_0');
            $config->increment('count_total_0');
            $config->increment('count_order_0');
           // Log::info("Contadores incrementados correctamente.");
        } catch (\Exception $e) {
            $errorMessage = "Error al incrementar los contadores: " . $e->getMessage();
            Log::error($errorMessage);
            return;
        }
    
        // Calcular time_00 y time_10
        try {
            $previousEntry = SensorCount::where('sensor_id', $config->id)
                ->where('value', 0)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $time_00 = $previousEntry ? now()->diffInSeconds($previousEntry->created_at) : 300;
    
            $previousEntry1 = SensorCount::where('sensor_id', $config->id)
                        ->where('value', 1)
                        ->orderBy('created_at', 'desc')
                        ->first();
            $time_10 = $previousEntry1 ? now()->diffInSeconds($previousEntry1->created_at) : 300;
    
            // Log::debug("Valores calculados: time_00: $time_00, time_10: $time_10");
        } catch (\Exception $e) {
            $errorMessage = "Error al calcular los tiempos: " . $e->getMessage();
            Log::error($errorMessage);
            return;
        }
    
        // Insertar el nuevo registro en la tabla sensor_counts
        try {
            SensorCount::create([
                'name' => $config->name,
                'value' => $value,
                'sensor_id' => $config->id, // Asegúrate de que este campo esté presente
                'production_line_id' => $config->production_line_id,
                'model_product' => $productName,
                'orderId' => $orderId,
                'count_total_0' => $config->count_total_0, // Ya incrementado arriba
                'count_shift_0' => $config->count_shift_0, // Ya incrementado arriba
                'count_order_0' => $config->count_order_0, // Ya incrementado arriba
                'time_00' => $time_00,
                'time_10' => $time_10,
            ]);

          //  Log::info("Registro insertado en sensor_counts correctamente.");
        } catch (\Exception $e) {
            $errorMessage = "Error al insertar en sensor_counts: " . $e->getMessage();
            Log::error($errorMessage);
        }
    
        // Determinar la función a ejecutar basada en function_model_0
        switch ($config->function_model_0) {
            case 'none':
                $this->none($config, $value);
                break;
            case 'sendMqttValue0':
                $this->sendMqttValue0($config, $value);
                break;
            default:
                Log::warning("Función desconocida: {$config->function_model_0}");
                break;
        }
    }
    

    /**
     * Process the sensor data with model 1.
     *
     * @param \App\Models\Sensor $config Sensor configuration
     * @param string $value Sensor value (0 or 1)
     * @return void
     */
    private function process1Model($config, $value, $barcode)
    {

        // Log::info("Modelo 1: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}"); // Muestra el mensaje en la consola
        // Obtener el registro del barcode asociado

        if (!$barcode || !$barcode->order_notice) {
            Log::warning("No se encontró el registro del barcode o el campo order_notice está vacío.");
            // Extraer valores específicos del JSON
            $orderId = "0";
            $valuePerPackage = "0";
            $productName = "0";
            $unitsPerBox = "0";
            $totalPerBox = "0";
        }else{
            // Decodificar el JSON almacenado en order_notice
            $orderNotice = json_decode($barcode->order_notice, true);
            
            // Extraer valores específicos del JSON
            $orderId = $orderNotice['orderId'] ?? null;
            $valuePerPackage = $orderNotice['refer']['value'] ?? null;
            $productName = $orderNotice['refer']['groupLevel'][0]['id'] ?? null;
            $unitsPerBox = $orderNotice['refer']['groupLevel'][0]['uds'] ?? null;
            $totalPerBox = $orderNotice['refer']['groupLevel'][0]['total'] ?? null;

            // Verificar valores extraídos
            $debugMessage = "Valores extraídos del JSON: OrderId: $orderId, ValuePerPackage: $valuePerPackage, ProductName: $productName";
            Log::debug($debugMessage);
        }

        
        

         // Intentar incrementar los contadores
        try {
            $config->increment('count_shift_1');
            $config->increment('count_total_1');
            $config->increment('count_order_1');
            Log::info("Contadores incrementados correctamente.");
        } catch (\Exception $e) {
            $errorMessage = "Error al incrementar los contadores: " . $e->getMessage();
            Log::error($errorMessage);
            return;
        }

        // Calcular time_11 y time_01
        try {
            $previousEntry = SensorCount::where('sensor_id', $config->id)
            ->where('value', 1)
            ->orderBy('created_at', 'desc')
            ->first();

            $time_11 = $previousEntry ? now()->diffInSeconds($previousEntry->created_at) : 300;

            $previousEntry0 = SensorCount::where('sensor_id', $config->id)
                    ->where('value', 0)
                    ->orderBy('created_at', 'desc')
                    ->first();
            $time_01 = $previousEntry0 ? now()->diffInSeconds($previousEntry0->created_at) : 300;

            $calculatedTimesMessage = "Valores calculados: time_11: $time_11, time_01: $time_01";
            Log::debug($calculatedTimesMessage);
        } catch (\Exception $e) {
            $errorMessage = "Error al calcular los tiempos: " . $e->getMessage();
            Log::error($errorMessage);
            return;
        }

        // Insertar el nuevo registro en la tabla sensor_counts
        try {
            SensorCount::create([
            'name' => $config->name,
            'value' => $value,
            'sensor_id' => $config->id, // Asegúrate de que este campo esté presente
            'production_line_id' => $config->production_line_id,
            'model_product' => $productName,
            'orderId' => $orderId,
            'count_total_1' => $config->count_total_1, // Ya incrementado arriba
            'count_shift_1' => $config->count_shift_1, // Ya incrementado arriba
            'count_order_1' => $config->count_order_1, // Ya incrementado arriba
            'time_11' => $time_11,
            'time_01' => $time_01,
            'unic_code_order'=>$config->unic_code_order,
            ]);

           // Log::info("Registro insertado en sensor_counts correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al insertar en sensor_counts: " . $e->getMessage());
        }

        

        // Determinar la función a ejecutar basada en function_model_1
        switch ($config->function_model_1) {
            case 'none':
                $this->none($config, $value);
                break;
            case 'sendMqttValue1':
                $this->sendMqttValue1($config, $value);
                break;
            default:
                Log::warning("Función desconocida: {$config->function_model_1}");
                break;
        }
    }

    private function none($config, $value)
    {
        // No hace nada, función placeholder
    }

    private function sendMqttValue0($config, $value)
    {

        // Determinar el estado
        $status = 3; // Default a "sin datos"

        // Verificar que $lastTime sea un número válido
        if ($value === 1 || $value === "1") {
         
                $status = 2; // buen estado

            } else {
                $status = 0; // Parada
 
        }
        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => $config->count_order_0,
            'status' => $status,
        ]);

        $processedMessageTotal = json_encode([
            'value' => $config->count_total_0,
            'status' => $status,
        ]);

        $processedMessageTotalShift = json_encode([
            'value' => $config->count_shift_0,
            'status' => $status,
        ]);
        // Publicar el mensaje a través de MQTT
        
        $this->publishMqttMessage($config->mqtt_topic_1 . '/infinite_counter', $processedMessageTotal);
        $this->publishMqttMessage($config->mqtt_topic_1, $processedMessage);

        //Log::info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1}: {$processedMessage}");
    }

    private function sendMqttValue1($config, $value)
    {
        // Obtener el último valor de time_11 desde la tabla sensor_counts
        $lastTime = SensorCount::where('sensor_id', $config->id)
            ->where('value', 1)
            ->orderBy('created_at', 'desc')
            ->value('time_11');

        // Obtener los valores de tiempo óptimo y multiplicadores
        $optimalTime = $config->optimal_production_time;
        $reducedSpeedMultiplier = $config->reduced_speed_time_multiplier;

        // Determinar el estado
        $status = 3; // Default a "sin datos"

        // Verificar que $lastTime sea un número válido
        if (is_numeric($lastTime)) {
            if ($lastTime <= $optimalTime) {
                $status = 2; // Buen estado
            } elseif ($lastTime <= $optimalTime * $reducedSpeedMultiplier) {
                $status = 1; // Velocidad reducida
            } else {
                $status = 0; // Parada
            }
        }

        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => $config->count_order_1,
            'status' => $status,
        ]);

        $processedMessageTotal = json_encode([
            'value' => $config->count_total_1,
            'status' => $status,
        ]);

        $processedMessageTotalShift = json_encode([
            'value' => $config->count_shift_1,
            'status' => $status,
        ]);
        // Publicar el mensaje a través de MQTT
        
        $this->publishMqttMessage($config->mqtt_topic_1 . '/infinite_counter', $processedMessageTotal);
        $this->publishMqttMessage($config->mqtt_topic_1, $processedMessage);

        Log::info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1} : {$processedMessage}");
    }


    private function publishMqttMessage($topic, $message)
    {
       try {
        // Inserta en la tabla mqtt_send_server1
        MqttSendServer1::createRecord($topic, $message);

        // Inserta en la tabla mqtt_send_server2
        MqttSendServer2::createRecord($topic, $message);

        Log::info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");

        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }
}
