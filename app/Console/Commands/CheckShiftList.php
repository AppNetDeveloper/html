<?php
// Nuevo comando Artisan
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList;
use App\Models\Barcode;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use Carbon\Carbon;

class CheckShiftList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check shift list and publish MQTT message if current time matches start time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__Construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            // Obtener el día actual de la semana
            $dayOfWeek = Carbon::now()->isoWeekday();

            // Verificar si es de lunes a viernes
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                // Obtener la hora actual
                $currentTime = Carbon::now()->format('H:i:s');

                // Buscar en la tabla shift_list los turnos que coincidan con la hora actual
                $shifts = ShiftList::where('start', $currentTime)->get();

                foreach ($shifts as $shift) {
                    // Buscar el registro en barcodes con el mismo production_line_id
                    $barcode = Barcode::where('production_line_id', $shift->production_line_id)->first();

                    if ($barcode) {
                        // Crear el topic y el mensaje JSON
                        $mqttTopic = $barcode->mqtt_topic_barcodes . '/shift';
                        $jsonMessage = json_encode([
                            'shift_type' => 'Turno Programado',
                            'event' => 'start',
                            'duration' => 406
                        ]);

                        // Publicar el mensaje MQTT
                        $this->publishMqttMessage($mqttTopic, $jsonMessage);
                    }
                }
            }

            // Esperar 60 segundos antes de la próxima verificación
            sleep(60); // Puedes ajustar el intervalo de espera según tus necesidades

            // Verificación de interrupción limpia
            if ($this->shouldStop()) {
                return 0;
            }
        }
    }

    /**
     * Publicar mensaje MQTT en las tablas mqtt_send_server1 y mqtt_send_server2
     *
     * @param string $topic
     * @param string $message
     * @return void
     */
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

    /**
     * Verificación para detener el proceso de forma segura
     *
     * @return bool
     */
    private function shouldStop()
    {
        // Aquí podrías implementar una lógica para detener el bucle de forma controlada
        // como verificar una señal de sistema o un archivo de control.
        return false;
    }
}
