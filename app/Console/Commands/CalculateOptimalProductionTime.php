<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\Barcode;
use App\Models\SensorCount;

class CalculateOptimalProductionTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-optimal-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the optimal production time for each product based on sensor data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $this->info("Starting the calculation of optimal production times...");

            // Obtener todos los sensores
            $sensors = Sensor::all();
            
            foreach ($sensors as $sensor) {
                // Obtener el barcoder asociado
                $barcode = Barcode::find($sensor->barcoder_id);

                if ($barcode && $barcode->order_notice) {
                    // Decodificar el JSON del campo order_notice
                    $orderNotice = json_decode($barcode->order_notice, true);

                    // Obtener el model_product de refer->groupLevel->id
                    $modelProduct = $orderNotice['refer']['groupLevel'][0]['id'] ?? null;

                    if ($modelProduct) {
                        // Buscar el sensor_count correspondiente
                        $sensorCount = SensorCount::where('model_product', $modelProduct)
                            ->where('sensor_id', $sensor->id)
                            ->where('value', '1')
                            ->where('created_at', '>=', Carbon::now()->subDays(30)) // Últimos 30 días
                            ->whereNotNull('time_11') // Asegurarse de que time_11 no sea NULL
                            ->where('time_11', '>', 0) // Asegurarse de que time_11 sea mayor a 0
                            ->orderBy('time_11', 'asc') // Obtener el más pequeño
                            ->first();


                        // Establecer un valor por defecto de 30 si time_11 es nulo, 0 o no se encuentra ningún registro
                        $optimalProductionTime = ($sensorCount && $sensorCount->time_11 > 0) ? $sensorCount->time_11 : 30;

                        // Si optimalProductionTime es 1, incrementar en 1
                        if ($optimalProductionTime == 1) {
                            $optimalProductionTime += 1;
                        }

                        // Actualizar el tiempo de producción óptimo en la tabla sensors
                        $sensor->optimal_production_time = $optimalProductionTime;
                        $sensor->save();

                        $this->info("Updated optimal production time for sensor: {$sensor->name} (Product: {$modelProduct})(tiempo sacado: {$optimalProductionTime})");
                    } else {
                        $this->warn("No model_product found in order_notice for sensor: {$sensor->name}");
                    }
                } else {
                    $this->warn("No barcode or order_notice found for sensor: {$sensor->name}");
                }
            }

            // Esperar 1 minutos antes de volver a ejecutar la lógica
            $this->info("Waiting for 1 minutes before the next run...");
            sleep(60); // Pausar 60 segundos (1 minutos)
        }

        return 0;
    }

}
