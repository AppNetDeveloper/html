<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\MonitorOee;
use Carbon\Carbon;
use App\Models\SensorCount;
use App\Models\Barcode;

class CalculateProductionMonitorOee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-monitor-oee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and handle production monitoring for sensors and modbuses based on monitor_oee table rules.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $this->info("Starting the production monitoring...");

            // Variables para acumular los valores totales de todos los sensores
            $totalRealCount = 0;
            $totalTheoreticalCount = 0;

            // Obtener todos los registros de monitor_oee
            $monitors = MonitorOee::all();

            foreach ($monitors as $monitor) {
                // Si sensor_active es 1, obtener todos los sensores de la línea de producción con sensor_type = 0
                if ($monitor->sensor_active == 1) {
                    $this->info("Fetching sensors with sensor_type = 0 for production line ID {$monitor->production_line_id}");
                    
                    // Filtrar los sensores por production_line_id y sensor_type = 0
                    $sensors = Sensor::where('production_line_id', $monitor->production_line_id)
                        ->where('sensor_type', 0)
                        ->get();

                    foreach ($sensors as $sensor) {
                        // Procesar y mostrar datos reales (count_order_1) y teóricos del sensor
                        [$real, $theoretical] = $this->processSensorData($sensor);

                        // Acumular los valores reales y teóricos
                        $totalRealCount += $real;
                        $totalTheoreticalCount += $theoretical;
                    }
                } else {
                    $this->info("Sensor calculations skipped for production line ID {$monitor->production_line_id} (sensor_active is 0).");
                }

                // Si modbus_active es 1, obtener todos los modbuses de la línea de producción
                if ($monitor->modbus_active == 1) {
                    $this->info("Fetching modbuses for production line ID {$monitor->production_line_id}");
                    $modbuses = Modbus::where('production_line_id', $monitor->production_line_id)->get();

                    foreach ($modbuses as $modbus) {
                        $this->processModbusData($modbus);
                    }
                } else {
                    $this->info("Modbus calculations skipped for production line ID {$monitor->production_line_id} (modbus_active is 0).");
                }
            }

            // Calcular el porcentaje acumulado
            if ($totalTheoreticalCount > 0) {
                $overallPercentage = ($totalRealCount / $totalTheoreticalCount) * 100;
            } else {
                $overallPercentage = 0; // Evitar división por 0
            }

            // Mostrar el total real, teórico y porcentaje sumado de todos los sensores procesados
            $this->info("Total Real count_order_1: {$totalRealCount}, Total Theoretical: {$totalTheoreticalCount}, Percentage: {$overallPercentage}%");

            // Esperar 1 segundo antes de volver a ejecutar la lógica
            $this->info("Waiting for 1 second before the next run...");
            sleep(1); // Pausar 1 segundo
        }

        return 0;
    }

    /**
     * Procesar los datos del sensor y devolver el real y teórico
     */
    private function processSensorData($sensor)
    {
        $this->info("Processing sensor: {$sensor->name}");

        // Obtener barcoder_id y buscar el registro en barcodes
        $barcoder = Barcode::find($sensor->barcoder_id);

        if ($barcoder) {
            $this->info("Fetching order_notice from barcoder_id: {$barcoder->id}");

            // Obtener order_notice (JSON) y extraer el orderId
            $orderNotice = json_decode($barcoder->order_notice, true);
            if (isset($orderNotice['orderId'])) {
                $orderId = $orderNotice['orderId'];
                $this->info("Extracted orderId: {$orderId}");

                // Extraer unic_code_order, optimal_production_time, y count_order_1 del sensor
                $unicCodeOrder = $sensor->unic_code_order;
                $optimalProductionTime = $sensor->optimal_production_time ?? 30; // Tiempo óptimo por defecto
                $countOrder1 = $sensor->count_order_1; // El valor real es count_order_1

                // Buscar en sensor_counts la primera línea que coincida con el orderId y unic_code_order
                $firstSensorCount = SensorCount::where('orderId', $orderId)
                    ->where('unic_code_order', $unicCodeOrder)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($firstSensorCount) {
                    $this->info("Found matching record in sensor_counts.");

                    // Obtener el tiempo de creación del primer registro
                    $createdAt = Carbon::parse($firstSensorCount->created_at);
                    $now = Carbon::now();

                    // Calcular la diferencia de tiempo en segundos
                    $timeWorkOrderFromShift = $now->diffInSeconds($createdAt);
                    $this->info("Time difference (timeWorkOrderFromShift): {$timeWorkOrderFromShift} seconds");

                    // Calcular cuántas cajas se deberían haber producido teóricamente al 100%
                    $theoreticalBoxes = floor($timeWorkOrderFromShift / $optimalProductionTime);

                    // Calcular porcentaje de eficiencia entre real y teórico
                    if ($theoreticalBoxes > 0) {
                        $percentage = ($countOrder1 / $theoreticalBoxes) * 100;
                    } else {
                        $percentage = 0; // Evitar división por 0
                    }

                    // Mostrar real, teórico y porcentaje por cada sensor
                    $this->info("Sensor '{$sensor->name}' - Real (count_order_1): {$countOrder1}, Theoretical: {$theoreticalBoxes}, Percentage: {$percentage}%");

                    // Devolver valores reales y teóricos para la suma global
                    return [$countOrder1, $theoreticalBoxes];
                } else {
                    $this->info("No matching record found in sensor_counts for orderId: {$orderId} and unic_code_order: {$unicCodeOrder}");
                }
            } else {
                $this->info("No orderId found in order_notice JSON.");
            }
        } else {
            $this->info("No barcoder found for barcoder_id: {$sensor->barcoder_id}");
        }

        // Si no se encuentran datos, devolvemos 0
        return [0, 0];
    }

    /**
     * Procesar los datos del modbus y mostrar información relevante
     */
    private function processModbusData($modbus)
    {
        $this->info("Processing modbus: {$modbus->name}");

        // Mostrar información relevante del modbus
        $this->info("Modbus '{$modbus->name}' - Last value: {$modbus->last_value}");
    }
}
