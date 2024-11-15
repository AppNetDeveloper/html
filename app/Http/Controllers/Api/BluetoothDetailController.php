<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BluetoothDetail;
use App\Models\BluetoothList;
use App\Models\BluetoothAnt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use GuzzleHttp\Client;


class BluetoothDetailController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validación de los datos recibidos
            $validator = Validator::make($request->all(), [
                'mac' => 'required|string',
                'rssi' => 'required|integer',
                'change' => 'required|string',
                'antenna_name' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buscar la antena Bluetooth por nombre
            $bluetoothAnt = BluetoothAnt::where('name', $request->input('antenna_name'))->first();
            if (!$bluetoothAnt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Antena Bluetooth no encontrada'
                ], 404);
            }

            // Buscar o crear el registro de BluetoothDetail por `mac`
            $bluetoothDetail = BluetoothDetail::where('mac', $request->input('mac'))->first();
            if (!$bluetoothDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'MAC no en database'
                ], 404);
            }

            // Incrementar contadores según el cambio detectado
            $bluetoothDetail->increment('count_total');
            $bluetoothDetail->increment('count_total_1');
            $bluetoothDetail->increment('count_shift_1');
            $bluetoothDetail->increment('count_order_1');

            $readingId=$bluetoothDetail->bluetooth_reading_id;
            $productionLineId=$bluetoothAnt->production_line_id;
            $detailId=$bluetoothDetail->id;
            $nameDetail=$bluetoothDetail->name;
            // Crear el registro en `bluetooth_list`, incluyendo `rssi`
            $bluetoothList = BluetoothList::create([
                'name' => $nameDetail,
                'value' => '1',
                'production_line_id' => $productionLineId,
                'bluetooth_detail_id' => $detailId,
                'bluetooth_reading_id' => $readingId, // Si corresponde a una lectura específica
                'bluetooth_ant_name' => $request->input('antenna_name'), // Guardamos el nombre de la antena
                'model_product' => '1',
                'orderId' => $bluetoothDetail->orderId ?? null,
                'count_total' => $bluetoothDetail->count_total,
                'count_total_1' => $bluetoothDetail->count_total_1,
                'count_shift_1' => $bluetoothDetail->count_shift_1,
                'count_order_1' => $bluetoothDetail->count_order_1,
                'time_11' => $bluetoothDetail->time_11 ?? now(),
                'mac' => $request->input('mac'),
                'rssi' => $request->input('rssi'), // Añadimos rssi aquí
                'change' => $request->input('change')
            ]);
            // Llamar a la función `sendAlert` si `send_alert` está habilitado
            if ($bluetoothDetail->send_alert && $bluetoothDetail->last_status_detect == "0") {
                $this->sendAlert($bluetoothDetail, $request->input('antenna_name'));
            }

            // Actualizar los campos `last_status_detect` y `last_ant_detect`
            $bluetoothDetail->update([
                'last_status_detect' => '1',  // Marcado como conectado
                'last_ant_detect' => $request->input('antenna_name')  // Guardamos el nombre de la antena
            ]);



            return response()->json([
                'success' => true,
                'message' => 'Registro insertado en Bluetooth list con éxito',
                'bluetooth_list' => $bluetoothList
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error en el método store: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud ',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Función para enviar alertas cuando un dispositivo se detecta
     *
     * @param \App\Models\BluetoothDetail $detail
     * @return void
     */
    protected function sendAlert(BluetoothDetail $detail, $antenaName)
    {
        // Obtiene la URL de la API y el número de teléfono desde las variables de entorno
        $apiUrl = rtrim(env('WHATSAPP_LINK'), '/') . '/send-message';
        $phoneNumber = env('WHATSAPP_PHONE_NOT');
        $dateTime=Carbon::now()->format('Y-m-d H:i:s');
        
        // Configura el cliente HTTP
        $client = new Client([
            'timeout' => 3,
            'http_errors' => false,
            'verify' => false,
        ]);

        // Datos para enviar el mensaje de alerta
        $dataToSend = [
            'jid' => "{$phoneNumber}@s.whatsapp.net",
            'message' => "Alerta: Punto de Control {$antenaName} ha detectado: {$detail->name}. Fecha: {$dateTime}",
        ];

        try {
            // Llamada asíncrona a la API
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend,
            ]);

            // Maneja la respuesta de la API
            $promise->then(
                function ($response) {
                    Log::info("Mensaje de alerta enviado correctamente: " . $response->getStatusCode());
                },
                function ($exception) {
                    Log::error("Error al enviar mensaje de alerta: " . $exception->getMessage());
                }
            );

            // Inicia el proceso sin bloquear el flujo principal
            $promise->wait(false);

        } catch (\Exception $e) {
            Log::error("Error en la llamada a la API de WhatsApp: " . $e->getMessage());
        }
    }

    public function getHistoryBluetooth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mac' => 'nullable|string',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
            'show' => 'nullable|in:all,10,latest',
            'change' => 'nullable|string'  // Validación del nuevo campo `change`
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = BluetoothList::with('bluetoothDetail')
            ->select('id', 'name', 'mac', 'change', 'rssi', 'bluetooth_ant_name', 'created_at');

        if ($request->filled('antenna_name') && $request->antenna_name !== 'all') {
            $query->where('bluetooth_ant_name', $request->antenna_name);
        }

        if ($request->filled('mac') && $request->mac !== 'all') {
            $query->where('mac', $request->mac);
        }

        if ($request->filled('change') && $request->change !== 'all') {
            $query->where('change', $request->change);
        }

        if ($request->filled('date_start') && $request->date_start !== 'all') {
            $query->whereDate('created_at', '>=', Carbon::parse($request->date_start));
        }

        if ($request->filled('date_end') && $request->date_end !== 'all') {
            $query->whereDate('created_at', '<=', Carbon::parse($request->date_end));
        }

        if ($request->filled('show')) {
            switch ($request->show) {
                case '10':
                    $query->orderBy('created_at', 'desc')->limit(10);
                    break;
                case 'latest':
                    $query->orderBy('created_at', 'desc')->limit(1);
                    break;
                case 'all':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $bluetoothLists = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Listado de Bluetooth filtrado con éxito',
            'data' => $bluetoothLists
        ]);
    }

    public function getFilters()
    {
        $antennas = BluetoothAnt::select('name')->get(); // Obtener nombres de antenas
        $macs = BluetoothDetail::select('mac')->distinct()->pluck('mac');

        return response()->json([
            'success' => true,
            'message' => 'Filtros disponibles obtenidos con éxito',
            'antennas' => $antennas,
            'macs' => $macs
        ]);
    }
}
