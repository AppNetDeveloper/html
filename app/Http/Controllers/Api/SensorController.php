<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
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
     *     summary="Obtener todos los sensores organizados por línea de producción",
     *     tags={"Sensores"},
     *     @OA\Response(
     *         response=200,
     *         description="Datos de todos los sensores obtenidos correctamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="production_line_id", type="integer", example=1),
     *                 @OA\Property(property="sensors", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Sensor 1"),
     *                     @OA\Property(property="sensor_type", type="integer", example=0),
     *                     @OA\Property(property="mqtt_topic_sensor", type="string", example="sensor/topic/1"),
     *                     @OA\Property(property="value", type="integer", example=1)
     *                 ))
     *             )
     *         )
     *     )
     * )
     */public function getAllSensors()
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

}
