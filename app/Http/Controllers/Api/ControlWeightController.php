<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modbus;
use App\Models\ControlWeight;
use Illuminate\Http\Request;

class ControlWeightController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/control-weight/{token}",
     *     summary="Get latest control weight data by token",
     *     tags={"Control Weight"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="gross_weight", type="number"),
     *             @OA\Property(property="dimension", type="string"),
     *             @OA\Property(property="box_number", type="integer"),
     *             @OA\Property(property="last_control_weight", type="number"),
     *             @OA\Property(property="last_dimension", type="number"),
     *             @OA\Property(property="last_box_number", type="integer"),
     *             @OA\Property(property="last_barcoder", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Modbus not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDataByToken(Request $request, $token)
    {
        // Obtener el Modbus correspondiente por token
        $modbus = Modbus::where('token', $token)->first();

        if (!$modbus) {
            return response()->json(['error' => 'Modbus not found'], 404);
        }

        // Obtener el último control weight para el Modbus especificado
        $latestControlWeight = ControlWeight::where('modbus_id', $modbus->id)
            ->latest('created_at')
            ->first();

        // Prepara los datos para la respuesta
        $response = [
            'name' => (string) ($modbus->name ?? 'NoName'), // Asegúrate de que 'NoName' esté entre comillas
            'gross_weight' => (float) ($modbus->last_value ?? 0),
            'dimension' => (float) ($modbus->dimension ?? 0),
            'box_number' => (int) ($modbus->rec_box + 1 ?? 0),
            'last_control_weight' => (float) ($latestControlWeight->last_control_weight ?? 0),
            'last_dimension' => (float) ($latestControlWeight->last_dimension ?? 0),
            'last_box_number' => (int) ($modbus->rec_box ?? 0),
            'last_barcoder' => (string) ($latestControlWeight->last_barcoder ?? ''),
        ];

        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/control-weights/{token}/all",
     *     summary="Get all control weight data by token",
     *     tags={"Control Weight"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="last_control_weight", type="number"),
     *                 @OA\Property(property="last_dimension", type="number"),
     *                 @OA\Property(property="last_box_number", type="integer"),
     *                 @OA\Property(property="last_barcoder", type="string"),
     *                 @OA\Property(property="last_final_barcoder", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Modbus not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
       // Método para obtener todos los registros de control weight con filtrado por rango de fechas
    public function getAllDataByToken(Request $request, $token)
    {
        // Obtener el Modbus correspondiente por token
        $modbus = Modbus::where('token', $token)->first();

        if (!$modbus) {
            return response()->json(['error' => 'Modbus not found'], 404);
        }

        // Obtener parámetros de fecha del request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Consultar los datos filtrados por fecha
        $query = ControlWeight::where('modbus_id', $modbus->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Obtener los resultados
        $controlWeights = $query->orderBy('created_at', 'desc')->get();

        // Prepara los datos para la respuesta
        $response = $controlWeights->map(function ($weight) {
            return [
                'last_control_weight' => (float) ($weight->last_control_weight ?? 0),
                'last_dimension' => (float) ($weight->last_dimension ?? 0),
                'last_box_number' => (int) ($weight->last_box_number ?? 0),
                'last_barcoder' => (string) ($weight->last_barcoder ?? ''),
                'last_final_barcoder' => (string) ($weight->last_final_barcoder ?? ''),
                'created_at' => $weight->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($response);
    }
}
