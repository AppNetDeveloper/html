<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\OrderStat;
use App\Http\Controllers\Controller;
//anadir carbon
use Carbon\Carbon;


class OrderStatsController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/order-stats/last",
     *     summary="Obtener la última estadística de un pedido",
     *     description="Retorna la última estadística de pedidos para una línea de producción usando un token.",
     *     tags={"OrderStats"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="some-production-line-token"),
     *             @OA\Property(property="production_line_id", type="integer", example=1),
     *             @OA\Property(property="order_id", type="string", example="ORD12345"),
     *             @OA\Property(property="units", type="integer", example=100),
     *             @OA\Property(property="box", type="integer", example=10),
     *             @OA\Property(property="units_box", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Última estadística de pedido obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="production_line_name", type="string", example="Linea 1"),
     *             @OA\Property(property="order_id", type="string", example="ORD12345"),
     *             @OA\Property(property="units", type="integer", example=100),
     *             @OA\Property(property="created_at", type="string", example="2024-10-15 14:35:12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token es requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Token is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Línea de producción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Production line not found")
     *         )
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
        
        // Combina los datos de OrderStat con el nombre de la línea de producción en un solo JSON
        $response = $lastOrderStat->toArray();  // Convierte los datos de OrderStat a un array
        $response['production_line_name'] = $productionLine->name;  // Añade el nombre de la línea de producción al mismo array
    
        // Devuelve el array combinado en formato JSON
        return response()->json($response, 200);
    }
    /**
     * @OA\Post(
     *     path="/api/order-stats/between-dates",
     *     summary="Obtener estadísticas de pedidos entre fechas",
     *     description="Obtiene estadísticas de pedidos entre dos fechas dadas para una línea de producción específica, utilizando el token de la línea.",
     *     tags={"OrderStats"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="some-production-line-token"),
     *             @OA\Property(property="start_date", type="string", example="2024-10-01"),
     *             @OA\Property(property="end_date", type="string", example="2024-10-20")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas de pedidos entre fechas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="production_line_name", type="string", example="Linea 1"),
     *             @OA\Property(property="order_id", type="string", example="ORD12345"),
     *             @OA\Property(property="units", type="integer", example=100),
     *             @OA\Property(property="created_at", type="string", example="2024-10-15 14:35:12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Línea de producción no encontrada o sin estadísticas",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Production line not found or no order stats found for the specified dates")
     *         )
     *     )
     * )
     */
    public function getOrderStatsBetweenDates(Request $request)
    {
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
    
        // Validar los datos de entrada y establecer fechas predeterminadas si no existen
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
    
        if (!$startDate || !$endDate) {
            $today = now()->format('Y-m-d');
            $startDate = $today . ' 00:00:00';
            $endDate = $today . ' 23:59:59';
        } else {
            // Formatear las fechas en caso de que no estén en el formato correcto
            $startDate = Carbon::parse($startDate)->format('Y-m-d H:i:s');
            $endDate = Carbon::parse($endDate)->format('Y-m-d H:i:s');
        }
    
        $productionLineId = $productionLine->id;
    
        // Consultar las estadísticas de pedidos entre las fechas especificadas
        $orderStats = OrderStat::where('production_line_id', $productionLineId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    
        if ($orderStats->isEmpty()) {
            return response()->json(['error' => 'No order stats found for the specified dates'], 404);
        }
    
        $productionLineName = $productionLine->name;
        $response = $orderStats->map(function ($orderStat) use ($productionLineName) {
            $orderStatArray = $orderStat->toArray();
            $orderStatArray['production_line_name'] = $productionLineName;
            return $orderStatArray;
        });
    
        return response()->json($response, 200);
    }
    
}