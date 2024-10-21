<?php

namespace App\Http\Controllers;

use App\Models\Scada;
use Illuminate\Http\Request;

class ScadaController extends Controller
{
    // Mostrar todos los registros
    public function index()
    {
        $items = Scada::all();
        return response()->json($items);
    }

    // Crear un nuevo registro
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'name' => 'required|string|max:255',
        ]);

        $item = Scada::create($validated);

        return response()->json($item, 201);
    }

    // Mostrar un registro específico
    public function show($id)
    {
        $item = Scada::findOrFail($id);
        return response()->json($item);
    }

    // Actualizar un registro
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'name' => 'required|string|max:255',
        ]);

        $item = Scada::findOrFail($id);
        $item->update($validated);

        return response()->json($item);
    }

    // Eliminar un registro
    public function destroy($id)
    {
        $item = Scada::findOrFail($id);
        $item->delete();

        return response()->json(null, 204);
    }
}
