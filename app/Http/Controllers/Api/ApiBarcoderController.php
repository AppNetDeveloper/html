<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class ApiBarcoderController extends Controller
{
    private function publishMqttMessage($topic, $message)
    {
        $server = env('MQTT_SERVER');
        $port = intval(env('MQTT_PORT'));
        $clientId = uniqid();

        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME'));
        $connectionSettings->setPassword(env('MQTT_PASSWORD'));

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            $mqtt->connect($connectionSettings, true);

            Log::info('Connected to MQTT server.', [
                'server' => $server,
                'port' => $port,
                'clientId' => $clientId
            ]);

            $mqtt->publish($topic, json_encode($message), 0);

            Log::info('Published MQTT message.', [
                'topic' => $topic,
                'message' => $message
            ]);

            $mqtt->disconnect();

            Log::info('Disconnected from MQTT server.');
        } catch (\Exception $e) {
            Log::error('Failed to publish MQTT message.', [
                'error' => $e->getMessage(),
                'topic' => $topic,
                'message' => $message
            ]);
        }
    }
     /**
     * @OA\Post(
     *     path="/api/barcode",
     *     summary="Process barcode actions",
     *     tags={"Barcode"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="barcoder", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="machine_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comprobación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="comando", type="object"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="barcode", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="machineId", type="string"),
     *             @OA\Property(property="mqttTopicBarcodes", type="string"),
     *             @OA\Property(property="mqttTopicOrders", type="string"),
     *             @OA\Property(property="mqttTopicFinish", type="string"),
     *             @OA\Property(property="mqttTopicPause", type="string"),
     *             @OA\Property(property="opeId", type="string"),
     *             @OA\Property(property="orderNotice", type="string"),
     *             @OA\Property(property="lastBarcode", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Proceso interrumpido: machine_id inválido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Proceso interrumpido: token inválido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function barcode(Request $request)
    {


        $barcodeValue = $request->query('barcoder', $request->input('barcoder'));
        $token = $request->query('token', $request->input('token'));
        $machineId = $request->query('machine_id', $request->input('machine_id'));


        $barcode = Barcode::where('machine_id', $machineId)->first();

        if (!$barcode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proceso interrumpido: machine_id inválido'
            ], 400);
        }

        if ($barcode->token !== $token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proceso interrumpido: token inválido'
            ], 401);
        }

        $mqttTopicBarcodes = $barcode->mqtt_topic_barcodes;
        $mqttTopicOrders = $barcode->mqtt_topic_orders;
        $mqttTopicFinish = $barcode->mqtt_topic_finish;
        $mqttTopicPause = $barcode->mqtt_topic_pause;
        $opeId = $barcode->ope_id;
        $orderNotice = $barcode->order_notice;
        $lastBarcode = $barcode->last_barcode;

        $orderNoticeData = json_decode($orderNotice, true);
        $orderId = $orderNoticeData['orderId'] ?? null;

        $comando = [];
        $mqttTopic = null; // Initialize to null to avoid errors

        if (in_array($lastBarcode, ['FINALIZAR', 'PAUSAR', null, '']) && $barcodeValue === 'INICIAR') {
            // Case 1: lastBarcode is FINALIZAR, PAUSAR, NULL, or empty, and barcodeValue is INICIAR
            $comando = [
                "action" => 0,
                "orderId" => $orderId,
                "machineId" => $machineId,
                "opeId" => "ENVASADO"
            ];
            $mqttTopic = $mqttTopicBarcodes;
            $this->publishMqttMessage($mqttTopic, $comando);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'INICIAR') {
            // Case 2: lastBarcode and barcodeValue are both INICIAR
            $comando = [
                "action" => 1,
                "orderId" => $orderId,
                "machineId" => $machineId,
                "opeId" => "ENVASADO"
            ];
            $mqttTopic = $mqttTopicBarcodes;

            Log::info('Preparing to publish first MQTT message.', [
                'topic' => $mqttTopic,
                'comando' => $comando
            ]);

            $this->publishMqttMessage($mqttTopic, $comando);
            Log::info('Mensaje MQTT enviado.', [
                'topic' => $mqttTopic,
                'comando' => $comando
            ]);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();

            sleep(3); // Wait 3 seconds

            // Re-fetch potentially updated order ID
            $updatedBarcode = Barcode::where('machine_id', $machineId)->first();
            $updatedOrderNotice = json_decode($updatedBarcode->order_notice, true);
            $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;

            $comando = [
                "action" => 0,
                "orderId" => $updatedOrderId, // Use the updated order ID
                "machineId" => $machineId,
                "opeId" => "ENVASADO"
            ];
            $mqttTopic = $mqttTopicBarcodes;

            $this->publishMqttMessage($mqttTopic, $comando);
            Log::info('Mensaje MQTT enviado.', [
                'topic' => $mqttTopic,
                'comando' => $comando
            ]);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'FINALIZAR') {
            // Case 3: lastBarcode is INICIAR and barcodeValue is FINALIZAR
            $comando = [
                "orderId" => $orderId
            ];
            $mqttTopic = $mqttTopicFinish;
            $this->publishMqttMessage($mqttTopic, $comando);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'PAUSAR') {
            // Case 4: lastBarcode is INICIAR and barcodeValue is PAUSAR
            $comando = [
                "orderId" => $orderId
            ];
            $mqttTopic = $mqttTopicPause;
            $this->publishMqttMessage($mqttTopic, $comando);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        }



        return response()->json([
            'comando' => $comando,
            'status' => 'success',
            'message' => 'Comprobación exitosa',
            'barcode' => $barcodeValue,
            'token' => $token,
            'machineId' => $machineId,
            'mqttTopicBarcodes' => $mqttTopicBarcodes,
            'mqttTopicOrders' => $mqttTopicOrders,
            'mqttTopicFinish' => $mqttTopicFinish,
            'mqttTopicPause' => $mqttTopicPause,
            'opeId' => $opeId,
            'orderNotice' => $orderNotice,
            'lastBarcode' => $lastBarcode,
        ]);
    }
}
