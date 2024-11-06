<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Modbus;
use App\Models\ApiQueuePrint;

class StoreQueueController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/queue-print",
     *     summary="Store a value in the print queue (GET)",
     *     tags={"Queue"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="url_back",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="url"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="token_back",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Valor agregado a la cola de impresión",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/queue-print",
     *     summary="Store a value in the print queue (POST)",
     *     tags={"Queue"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="url_back", type="string", format="url"),
     *             @OA\Property(property="token_back", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Valor agregado a la cola de impresión",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function storeQueuePrint(Request $request)
    {
        // Obtener parámetros de la solicitud
        $token = $request->query('token', $request->input('token'));
        $value = $request->query('value', $request->input('value'));
        $urlBack = $request->query('url_back', $request->input('url_back'));
        $tokenBack = $request->query('token_back', $request->input('token_back'));
    
        Log::debug("Solicitud recibida: " . $request->method() . " " . $request->fullUrl());
        Log::debug("Parámetros: token=$token, value=$value, url_back=$urlBack, token_back=$tokenBack");
    
        // Validación de datos
        try {
            $request->validate([
                'token' => 'required|exists:modbuses,token',
                'value' => 'required|string', // Asumiendo que el valor es una cadena
                'url_back' => 'required|url', // Validar que sea una URL válida
                'token_back' => 'nullable|string', // Ahora es opcional, puede ser null o vacío
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Error de validación: " . $e->getMessage());
            return response()->json(['error' => 'Datos inválidos'], 422);
        }
    
        // Si token_back es null, asignar una cadena vacía
        $tokenBack = $tokenBack ?? '';
    
        // Obtener el Modbus asociado al token
        try {
            $modbus = Modbus::where('token', $token)->firstOrFail();
            Log::debug("Modbus encontrado: ID {$modbus->id}");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Modbus no encontrado para el token: $token");
            return response()->json(['error' => 'Token inválido'], 401); // No autorizado
        }
    
        // Crear el registro en api_queue_prints
        try {
            ApiQueuePrint::create([
                'modbus_id' => $modbus->id,
                'value' => $value,
                'url_back' => $urlBack, // Guardar la URL de retorno
                'token_back' => $tokenBack, // Guardar el token de retorno o una cadena vacía
            ]);
            Log::debug("Valor agregado a la cola de impresión: $value, url_back=$urlBack, token_back=$tokenBack");
        } catch (\Exception $e) {
            Log::error("Error al crear registro en api_queue_prints: " . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    
        return response()->json(['message' => 'Valor agregado a la cola de impresion']);
    }

/**
 * @OA\Get(
 *     path="/api/queue-print-list",
 *     summary="Get a list of print queue items",
 *     tags={"Queue"},
 *     @OA\Parameter(
 *         name="token",
 *         in="query",
 *         required=true,
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="used",
 *         in="query",
 *         required=true,
 *         @OA\Schema(
 *             type="string",
 *             enum={"0", "1", "all"}
 *         ),
 *         description="0: only unused, 1: only used, all: all items"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Listado de la cola de impresión",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="modbus_id", type="integer"),
 *                 @OA\Property(property="value", type="string"),
 *                 @OA\Property(property="used", type="boolean"),
 *                 @OA\Property(property="url_back", type="string"),
 *                 @OA\Property(property="token_back", type="string"),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Token inválido",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

    public function getQueuePrints(Request $request)
    {
        $token = $request->query('token');
        $used = $request->query('used');

        if (!$token || !in_array($used, ['0', '1', 'all'])) {
            return response()->json(['error' => 'Token y parámetro used requeridos'], 422);
        }

        // Validar el token
        $modbus = Modbus::where('token', $token)->first();
        if (!$modbus) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        // Filtrar según el valor de used
        $query = ApiQueuePrint::where('modbus_id', $modbus->id);
        if ($used !== 'all') {
            $query->where('used', $used == '1');
        }

        $queuePrints = $query->get();

        return response()->json($queuePrints);
    }
    
}
