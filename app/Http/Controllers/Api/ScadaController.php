<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scada;
use App\Models\Modbus;
use App\Models\ScadaList;
use Illuminate\Http\Request;

class ScadaController extends Controller
{
    public function getModbusesByScadaToken($token)
    {
        // Buscar el registro en scada por el token
        $scada = Scada::where('token', $token)->first();

        if (!$scada) {
            return response()->json(['error' => 'Scada not found'], 404);
        }

        // Obtener el production_line_id de scada
        $productionLineId = $scada->production_line_id;

        // Buscar en modbuses donde production_line_id coincida y model_name sea 'weight'
        $modbuses = Modbus::where('production_line_id', $productionLineId)
                          ->where('model_name', 'weight')
                          ->get();

        if ($modbuses->isEmpty()) {
            return response()->json(['error' => 'No modbus lines found'], 404);
        }

        // Crear un array de respuesta con los datos relevantes de cada línea
        $modbusData = $modbuses->map(function ($modbus) {
            // Buscar en scada_list por el modbus_id actual
            $scadaList = ScadaList::where('modbus_id', $modbus->id)->first();

            // Si encontramos el registro en scada_list, extraemos fillinglevels, material_type, m3 y density
            if ($scadaList) {
                $materialType = $scadaList->materialType;
                $materialTypeName = $materialType ? $materialType->name : 'N/A';
                $density = $materialType ? $materialType->density : 'N/A';

                return [
                    'id' => $modbus->id,
                    'name' => $modbus->name,
                    'mqtt_topic_modbus' => $modbus->mqtt_topic_modbus,
                    'dimension' => $modbus->dimension,
                    'max_kg' => $modbus->max_kg,
                    'last_kg' => $modbus->last_kg,
                    'rec_box' => $modbus->rec_box,
                    'last_value' => $modbus->last_value,
                    'fillinglevels' => $scadaList->fillinglevels,  // Añadir fillinglevels
                    'material_type' => $materialTypeName,           // Añadir nombre del material
                    'density' => $density,                          // Añadir density del material
                    'm3' => $scadaList->m3                          // Añadir m3
                ];
            }

            // Si no se encuentra en scada_list, devolver valores por defecto
            return [
                'id' => $modbus->id,
                'name' => $modbus->name,
                'mqtt_topic_modbus' => $modbus->mqtt_topic_modbus,
                'dimension' => $modbus->dimension,
                'max_kg' => $modbus->max_kg,
                'last_kg' => $modbus->last_kg,
                'rec_box' => $modbus->rec_box,
                'last_value' => $modbus->last_value,
                'fillinglevels' => 'N/A',
                'material_type' => 'N/A',
                'density' => 'N/A',
                'm3' => 'N/A'
            ];
        });

        // Incluir el name de la línea scada en el JSON
        $response = [
            'scada_name' => $scada->name,
            'modbus_lines' => $modbusData
        ];

        return response()->json($response, 200);
    }
}
