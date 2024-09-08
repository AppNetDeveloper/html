<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

use App\Models\Modbus;
use App\Models\ApiQueuePrint;
use App\Models\ProductionLine;
use App\Models\Barcode;

class GetTokenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/production-lines/{customerToken}",
     *     summary="Obtener líneas de producción por token de cliente (GET)",
     *     description="Retorna los tokens y nombres de las líneas de producción asociadas al cliente.",
     *     operationId="getProductionLinesByCustomerTokenGet",
     *     tags={"Production Lines"},
     *     @OA\Parameter(
     *         name="customerToken",
     *         in="path",
     *         description="El token del cliente",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre de la línea de producción"),
     *                 @OA\Property(property="token", type="string", description="Token de la línea de producción")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/production-lines",
     *     summary="Obtener líneas de producción por token de cliente (POST)",
     *     description="Retorna los tokens y nombres de las líneas de producción asociadas al cliente.",
     *     operationId="getProductionLinesByCustomerTokenPost",
     *     tags={"Production Lines"},
     *     @OA\RequestBody(
     *         description="El token del cliente",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="customerToken", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre de la línea de producción"),
     *                 @OA\Property(property="token", type="string", description="Token de la línea de producción")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     */
    public function getProductionLinesByCustomerToken(Request $request, $customerToken = null)
    {
        // Si el token no viene en la URL (GET), lo tomamos del body (POST)
        if (!$customerToken) {
            $customerToken = $request->input('customerToken');
        }

        // Buscar el cliente por el token
        $customer = Customer::where('token', $customerToken)->first();

        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Obtener las líneas de producción asociadas al cliente
        $productionLines = ProductionLine::where('customer_id', $customer->id)->get();

        // Formatear la respuesta
        $response = $productionLines->map(function ($productionLine) {
            return [
                'name' => $productionLine->name,
                'token' => $productionLine->token, 
            ];
        });

        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/modbus-info/{customerToken}",
     *     summary="Obtener información de Modbuses por token de cliente (GET)",
     *     description="Retorna los nombres y tokens de los Modbuses asociados al cliente.",
     *     operationId="getModbusInfoByCustomerTokenGet",
     *     tags={"Modbus Info"},
     *     @OA\Parameter(
     *         name="customerToken",
     *         in="path",
     *         description="El token del cliente",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre del Modbus"),
     *                 @OA\Property(property="token", type="string", description="Token del Modbus")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/modbus-info",
     *     summary="Obtener información de Modbuses por token de cliente (POST)",
     *     description="Retorna los nombres y tokens de los Modbuses asociados al cliente.",
     *     operationId="getModbusInfoByCustomerTokenPost",
     *     tags={"Modbus Info"},
     *     @OA\RequestBody(
     *         description="El token del cliente",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="customerToken", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre del Modbus"),
     *                 @OA\Property(property="token", type="string", description="Token del Modbus")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     */
    public function getModbusInfoByCustomer(Request $request, $customerToken = null)
    {
        // Si el token no viene en la URL (GET), lo tomamos del body (POST)
        if (!$customerToken) {
            $customerToken = $request->input('customerToken');
        }

        // Buscar el cliente por el token
        $customer = Customer::where('token', $customerToken)->first();

        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Obtener las líneas de producción asociadas al cliente
        $productionLines = ProductionLine::where('customer_id', $customer->id)->get();

        // Obtener los Modbuses asociados a esas líneas de producción
        $modbuses = Modbus::whereIn('production_line_id', $productionLines->pluck('id'))->get();

        // Formatear la respuesta
        $response = $modbuses->map(function ($modbus) {
            return [
                'name' => $modbus->name,
                'token' => $modbus->token
            ];
        });

        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/barcode-info-by-customer/{customerToken}",
     *     summary="Obtener información de códigos de barras por token de cliente (GET)",
     *     description="Retorna los nombres y tokens de los códigos de barras asociados al cliente.",
     *     operationId="getBarcodeInfoByCustomerTokenGet",
     *     tags={"Barcode Info"},
     *     @OA\Parameter(
     *         name="customerToken",
     *         in="path",
     *         description="El token del cliente",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre del código de barras"),
     *                 @OA\Property(property="token", type="string", description="Token del código de barras")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     *
     * @OA\Post(
     *     path="/api/barcode-info-by-customer",
     *     summary="Obtener información de códigos de barras por token de cliente (POST)",
     *     description="Retorna los nombres y tokens de los códigos de barras asociados al cliente.",
     *     operationId="getBarcodeInfoByCustomerTokenPost",
     *     tags={"Barcode Info"},
     *     @OA\RequestBody(
     *         description="El token del cliente",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="customerToken", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre del código de barras"),
     *                 @OA\Property(property="token", type="string", description="Token del código de barras")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     */
    public function getBarcodeInfoByCustomer(Request $request, $customerToken = null)
    {
        // Si el token no viene en la URL (GET), lo tomamos del body (POST)
        if (!$customerToken) {
            $customerToken = $request->input('customerToken');
        }

        // Buscar el cliente por el token
        $customer = Customer::where('token', $customerToken)->first();

        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Obtener las líneas de producción asociadas al cliente
        $productionLines = ProductionLine::where('customer_id', $customer->id)->get();

        // Obtener los códigos de barras asociados a esas líneas de producción
        $barcodes = Barcode::whereIn('production_line_id', $productionLines->pluck('id'))->get();

        // Formatear la respuesta
        $response = $barcodes->map(function ($barcode) {
            return [
                'name' => $barcode->name,
                'token' => $barcode->token
            ];
        });

        return response()->json($response);
    }
}
