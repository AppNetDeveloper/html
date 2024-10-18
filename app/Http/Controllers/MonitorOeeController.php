<?php

namespace App\Http\Controllers;

use App\Models\MonitorOee;
use App\Models\ProductionLine;
use Illuminate\Http\Request;

class MonitorOeeController extends Controller
{
    /**
     * Muestra la lista de monitores OEE.
     */
    public function index(Request $request)
    {
        $production_line_id = $request->input('production_line_id');
        
        // Obtener los monitores OEE asociados a la línea de producción
        $monitorOees = MonitorOee::where('production_line_id', $production_line_id)->get();
    
        return view('oee.index', compact('monitorOees', 'production_line_id'));
    }
    
    

    /**
     * Muestra el formulario para crear un nuevo Monitor OEE.
     */
    public function create(Request $request)
    {
        $production_line_id = $request->production_line_id;
        $productionLines = ProductionLine::all();
        
        // Cambia la referencia de 'monitoroees.create' a 'oee.create'
        return view('oee.create', compact('production_line_id', 'productionLines'));
    }
    

    /**
     * Almacena un nuevo Monitor OEE en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'mqtt_topic' => 'required|string|max:255',
            'mqtt_topic2' => 'nullable|string|max:255',
            'topic_oee' => 'nullable|string|max:255',
            'sensor_active' => 'nullable|boolean',
            'modbus_active' => 'nullable|boolean',
            'time_start_shift' => 'nullable|date',
        ]);

        MonitorOee::create($request->all());

        return redirect()->route('monitoroees.index')->with('success', 'Monitor OEE creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar un Monitor OEE existente.
     */
    public function edit($id)
    {
        $monitorOee = MonitorOee::findOrFail($id);
        $productionLines = ProductionLine::all();
        return view('oee.edit', compact('monitorOee', 'productionLines'));
    }

    /**
     * Actualiza un Monitor OEE en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'mqtt_topic' => 'required|string|max:255',
            'mqtt_topic2' => 'nullable|string|max:255',
            'topic_oee' => 'nullable|string|max:255',
            'sensor_active' => 'nullable|boolean',
            'modbus_active' => 'nullable|boolean',
            'time_start_shift' => 'nullable|date',
        ]);

        $monitorOee = MonitorOee::findOrFail($id);
        $monitorOee->update($request->all());

        return redirect()->route('monitoroees.index')->with('success', 'Monitor OEE actualizado exitosamente.');
    }

    /**
     * Elimina un Monitor OEE de la base de datos.
     */
    public function destroy($id)
    {
        $monitorOee = MonitorOee::findOrFail($id);
        $monitorOee->delete();

        return redirect()->route('monitoroees.index')->with('success', 'Monitor OEE eliminado exitosamente.');
    }
}
