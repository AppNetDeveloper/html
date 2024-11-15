<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RfidDetail;
use App\Models\RfidList;
use App\Models\RfidAnt;
use App\Models\RfidReading;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use GuzzleHttp\Client;

class RfidDetailController extends Controller
{
    public function store(Request $request)
    {
        try {
            // 1. Validación básica de los datos recibidos
            $validator = Validator::make($request->all(), [
                'epc' => 'required|string',
                'rssi' => 'required|integer',
                'serialno' => 'required|string',
                'tid' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // 2. Obtener la antena RFID
            $rfidAnt = RfidAnt::where('name',$request->input('antenna_name'))->first();
            if (!$rfidAnt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Antena RFID no encontrada'
                ], 404);
            }
    
            // 3. Obtener el rfid_reading usando el epc
            $rfidReading = RfidReading::where('epc', $request->input('epc'))->first();
            if (!$rfidReading) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFID reading no encontrado para el EPC proporcionado'
                ], 404);
            }
    
            // 4. Obtener el rfid_detail
        // 4. Intentar obtener el rfid_detail con manejo de errores
        try {
            $rfidDetail = RfidDetail::where('tid', $request->input('tid'))->first();

            if (!$rfidDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPC o TID no coinciden con los registros en RFID details. EPC: ' . $request->input('epc') . ' TID: ' . $request->input('tid')
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error al obtener rfid_detail: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener rfid_detail',
                'error' => $e->getMessage(),
            ], 500);
        }
    
            // 5. Actualizar contadores
            $rfidDetail->increment('count_total');
            $rfidDetail->increment('count_total_1');
            $rfidDetail->increment('count_shift_1');
            $rfidDetail->increment('count_order_1');
            
    
            // 6. Crear el registro en rfid_list
            $rfidList = RfidList::create([
                'name' => $rfidDetail->name,
                'value' => '1',
                'production_line_id' => $rfidAnt->production_line_id, // Usar el production_line_id de la antena
                'rfid_detail_id' => $rfidDetail->id,
                'rfid_reading_id' => $rfidReading->id,
                'rfid_ant_name' => $request->input('antenna_name'),
                'model_product' => '1',
                'orderId' => $rfidDetail->orderId,
                'count_total' => $rfidDetail->count_total,
                'count_total_1' => $rfidDetail->count_total_1,
                'count_shift_1' => $rfidDetail->count_shift_1,
                'count_order_1' => $rfidDetail->count_order_1,
                'time_11' => $rfidDetail->time_11,
                'epc' => $request->input('epc'),
                'tid' => $request->input('tid'),
                'rssi' => $request->input('rssi'),
                'serialno' => $request->input('serialno'),
                'ant' => $request->input('ant')
            ]);

            
                        // Llamar a la función `sendAlert` si `send_alert` está habilitado
                        if ($rfidDetail->send_alert && $rfidDetail->last_status_detect == "0" || $rfidDetail->send_alert && $rfidDetail->last_ant_detect !== $request->input('antenna_name')) {
                            $this->sendAlert($rfidDetail, $request->input('antenna_name'));
                        }
                        // Actualizar los campos `last_status_detect` y `last_ant_detect`
                        $rfidDetail->update([
                            'last_status_detect' => '1',  // Marcado como conectado
                            'last_ant_detect' => $request->input('antenna_name')  // Guardamos el nombre de la antena
                        ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Registro insertado en RFID list con éxito',
                'rfid_list' => $rfidList
            ], 201);
    
        } catch (\Exception $e) {
            Log::error("Error en el método store: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

       /**
     * Función para enviar alertas cuando un dispositivo se detecta
     *
     * @param \App\Models\RfidDetail $detail
     * @return void
     */
    protected function sendAlert(RfidDetail $detail, $antenaName)
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

    public function getHistoryRfid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epc' => 'nullable|string',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
            'show' => 'nullable|in:all,10,latest'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $query = RfidList::query();
    
        // Aplicar filtros solo si `antenna_name` no es `all`
        if ($request->filled('antenna_name') && $request->antenna_name !== 'all') {
            $query->where('rfid_ant_name', $request->antenna_name);
        }
    
        if ($request->filled('epc') && $request->epc !== 'all') {
            $query->where('epc', $request->epc);
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
    
        $rfidLists = $query->get();
    
        return response()->json([
            'success' => true,
            'message' => 'Listado de RFID filtrado con éxito',
            'data' => $rfidLists
        ]);
    }
    
    public function getFilters()
    {
        $antennas = RfidAnt::select('name')->get(); // Solo obtén el nombre de la antena
        $epcs = RfidDetail::select('epc')->distinct()->pluck('epc');
        $tids = RfidDetail::select('tid')->distinct()->pluck('tid');
    
        return response()->json([
            'success' => true,
            'antennas' => $antennas,
            'epcs' => $epcs,
            'tids' => $tids
        ]);
    }
        
}
