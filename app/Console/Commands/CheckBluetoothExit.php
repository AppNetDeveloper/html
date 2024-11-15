<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BluetoothDetail;
use App\Models\BluetoothList;
use Illuminate\Support\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\BluetoothAnt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CheckBluetoothExit extends Command
{
    protected $signature = 'bluetooth:check-exit';
    protected $description = 'Verifica si los dispositivos Bluetooth han salido de la zona de detección';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        while (true) {
            // Obtener todos los registros de bluetooth_details que tienen `search_out` habilitado
            $bluetoothDetails = BluetoothDetail::where('search_out', true)->get();

            foreach ($bluetoothDetails as $detail) {
                // Calcular la diferencia en segundos desde la última actualización
                $lastUpdate = Carbon::parse($detail->updated_at);
                $currentTime = Carbon::now();
                $differenceInSeconds = $currentTime->diffInSeconds($lastUpdate);

                if ($differenceInSeconds > 15) {
                    // Obtener el último registro de bluetooth_list para este dispositivo
                    $lastListEntry = BluetoothList::where('bluetooth_detail_id', $detail->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // Verificar si el último registro ya tiene el cambio "Dispositivo ha salido de la zona de detección"
                    if (!$lastListEntry || $lastListEntry->change !== 'Dispositivo ha salido de la zona de detección') {
                        
                        // Verificar si `bluetooth_reading_id` es válido en `bluetooth_details`
                        if (!$detail->bluetooth_reading_id) {
                            $this->info("Error: `bluetooth_reading_id` no válido para el dispositivo con MAC: {$detail->mac}");
                            continue; // Saltar esta iteración si no tiene un `bluetooth_reading_id` válido
                        }

                        // Crear una nueva entrada en bluetooth_list con el estado de salida
                        BluetoothList::create([
                            'name' => $detail->name,
                            'value' => '1',
                            'production_line_id' => $detail->production_line_id,
                            'bluetooth_detail_id' => $detail->id,
                            'bluetooth_reading_id' => $detail->bluetooth_reading_id,  // Asegurarse de que el ID es válido
                            'bluetooth_ant_name' => $lastListEntry ? $lastListEntry->bluetooth_ant_name : 'N/A',
                            'model_product' => '1',
                            'orderId' => $detail->orderId ?? null,
                            'count_total' => $detail->count_total,
                            'count_total_1' => $detail->count_total_1,
                            'count_shift_1' => $detail->count_shift_1,
                            'count_order_1' => $detail->count_order_1,
                            'time_11' => $detail->time_11 ?? now(),
                            'mac' => $detail->mac,
                            'change' => 'Dispositivo ha salido de la zona de detección',
                        ]);

                        $this->info("Registro creado en bluetooth_list para el dispositivo con MAC: {$detail->mac}");

                        // Llamar a la función `sendAlert` si `send_alert` está habilitado
                        if ($detail->send_alert && $lastListEntry) {
                            $this->sendAlert($detail, $lastListEntry->bluetooth_ant_name);
                        }else{
                            $this->info("Registro creado en bluetooth_list para el dispositivo con MAC: {$detail->mac}");
                        }
                        // Actualizar los campos last_status_detect y last_ant_detect en BluetoothDetail
                        $detail->update([
                            'last_status_detect' => '0',  // Marcado como desconectado
                            'last_ant_detect' => $lastListEntry ? $lastListEntry->bluetooth_ant_name : 'N/A',  // Nombre de la antena de la última entrada
                        ]);

                        
                    }
                }
            }

            $this->info('Ciclo de verificación completado. Esperando 5 segundos antes del siguiente ciclo...');
            sleep(5); // Esperar 5 segundos antes de la próxima iteración
        }
    }

    /**
     * Función para enviar alertas cuando un dispositivo sale de la zona de detección
     *
     * @param \App\Models\BluetoothDetail $detail
     * @return void
     */
    protected function sendAlert(BluetoothDetail $detail, $antenaName)
    {
        // Obtiene la URL de la API y el número de teléfono desde las variables de entorno
        $apiUrl = rtrim(env('WHATSAPP_LINK'), '/') . '/send-message';
        $phoneNumber = env('WHATSAPP_PHONE_NOT');
        $dateTime = Carbon::now()->format('Y-m-d H:i:s');
        
        // Configura el cliente HTTP
        $client = new Client([
            'timeout' => 3,
            'http_errors' => false,
            'verify' => false,
        ]);
    
        // Datos para enviar el mensaje de alerta, incluyendo la antena y el nombre del dispositivo
        $dataToSend = [
            'jid' => "{$phoneNumber}@s.whatsapp.net",
            'message' => "Alerta: Punto de Control {$antenaName} ha dejado de detectar: {$detail->name}. Fecha: {$dateTime}",
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
    
}
