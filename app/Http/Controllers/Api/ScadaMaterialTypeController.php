<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scada;
use App\Models\ScadaMaterialType;
use Illuminate\Http\Request;

class ScadaMaterialTypeController extends Controller
{
    public function getScadaMaterialByToken($token)
    {
        // Buscar el registro en Scada por el token
        $scada = Scada::where('token', $token)->first();

        if (!$scada) {
            return response()->json(['error' => 'Scada not found'], 404);
        }

        // Obtener los ScadaMaterialType relacionados con el Scada
        $materials = ScadaMaterialType::where('scada_id', $scada->id)->get();

        if ($materials->isEmpty()) {
            return response()->json(['error' => 'No materials found'], 404);
        }

        // Crear un array de respuesta con los nombres de los materiales
        $materialNames = $materials->map(function ($material) {
            return ['id' => $material->id,
            'name' => $material->name
        ];
        });

        // Retornar el resultado en JSON
        return response()->json($materialNames, 200);
    }
}
