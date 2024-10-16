<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\OrderStat;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

class OrderStatsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1/order-stats",
     *     summary="Obtener la última estadística de orden",
     *     tags={"OrderStats"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token de la línea de producción",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="production_line_id", type="integer"),
     *             @OA\Property(property="order_id", type="string"),
     *             @OA\Property(property="units", type="integer"),
     *             @OA\Property(property="box", type="integer"),
     *             @OA\Property(property="units_box", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token requerido"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Línea de producción no encontrada o sin estadísticas"
     *     )
     * )
     *
     * @OA\Post(
     *     path="/v1/order-stats",
     *     summary="Crear una nueva estadística de orden",
     *     tags={"OrderStats"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"production_line_id", "order_id", "units"},
     *             @OA\Property(property="production_line_id", type="integer", example=1),
     *             @OA\Property(property="order_id", type="string", example="12/4611"),
     *             @OA\Property(property="units", type="integer", example=1000),
     *             @OA\Property(property="box", type="integer", example=10),
     *             @OA\Property(property="units_box", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Creado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="production_line_id", type="integer"),
     *             @OA\Property(property="order_id", type="string"),
     *             @OA\Property(property="units", type="integer"),
     *             @OA\Property(property="box", type="integer"),
     *             @OA\Property(property="units_box", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Datos de entrada no válidos"
     *     )
     * )
     */
    public function getLastOrderStat(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validar los datos de entrada
            $request->validate([
                'production_line_id' => 'required|exists:production_lines,id',
                'order_id' => 'required|string|max:255',
                'units' => 'required|integer',
                'box' => 'nullable|integer',
                'units_box' => 'nullable|integer',
            ]);

            // Crear una nueva instancia de OrderStat
            $orderStat = new OrderStat();
            $orderStat->production_line_id = $request->input('production_line_id');
            $orderStat->order_id = $request->input('order_id');
            $orderStat->units = $request->input('units');
            $orderStat->box = $request->input('box');
            $orderStat->units_box = $request->input('units_box');

            // Guardar la nueva OrderStat
            $orderStat->save();

            // Devolver la respuesta en formato JSON
            return response()->json($orderStat, 201);
        }

        // Obtiene el token de la línea de producción desde la solicitud.
        $token = $request->input('token');

        if (!$token) {
            return response()->json(['error' => 'Token is required'], 400);
        }

        // Encuentra la línea de producción por el token.
        $productionLine = ProductionLine::where('token', $token)->first();

        if (!$productionLine) {
            return response()->json(['error' => 'Production line not found'], 404);
        }

        // Encuentra la última línea de 'order_stats' para la línea de producción encontrada.
        $lastOrderStat = OrderStat::where('production_line_id', $productionLine->id)->orderBy('created_at', 'desc')->first();

        if (!$lastOrderStat) {
            return response()->json(['error' => 'No order stats found for the production line'], 404);
        }

        // Devuelve la última línea encontrada en formato JSON.
        return response()->json($lastOrderStat, 200);
    }
}