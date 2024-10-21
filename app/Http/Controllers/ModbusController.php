<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modbus;
use Illuminate\Support\Facades\Validator;

class ModbusController extends Controller
{
    public function index($production_line_id)
    {
        $modbuses = Modbus::where('production_line_id', $production_line_id)->get();
        return view('modbuses.index', compact('modbuses', 'production_line_id'));
    }

    public function create($production_line_id)
    {
        return view('modbuses.create', compact('production_line_id'));
    }

    public function store(Request $request, $production_line_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Añadir validaciones adicionales para otros campos según sea necesario
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Modbus::create([
            'production_line_id' => $production_line_id,
            'name' => $request->name,
            'json_api' => $request->json_api,
            'mqtt_topic_modbus' => $request->mqtt_topic_modbus,
            'mqtt_topic_gross' => $request->mqtt_topic_gross,
            'mqtt_topic_control' => $request->mqtt_topic_control,
            'mqtt_topic_boxcontrol' => $request->mqtt_topic_boxcontrol,
            'token' => $request->token,
            'dimension_id' => $request->dimension_id,
            'dimension' => $request->dimension,
            'max_kg' => $request->max_kg,
            'rep_number' => $request->rep_number,
            'tara' => $request->tara,
            'tara_calibrate' => $request->tara_calibrate,
            'calibration_type' => $request->calibration_type,
            'min_kg' => $request->min_kg,
            'last_kg' => $request->last_kg,
            'last_rep' => $request->last_rep,
            'rec_box' => $request->rec_box,
            'last_value' => $request->last_value,
            'variacion_number' => $request->variacion_number,
            'model_name' => $request->model_name,
            'dimension_default' => $request->dimension_default,
            'dimension_max' => $request->dimension_max,
            'dimension_variacion' => $request->dimension_variacion,
            'offset_meter' => $request->offset_meter,
            'printer_id' => $request->printer_id,
        ]);

        return redirect()->route('modbuses.index', $production_line_id)->with('success', 'Modbus creado correctamente.');
    }

    public function edit($id)
    {
        $modbus = Modbus::findOrFail($id);
        return view('modbuses.edit', compact('modbus'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Añadir validaciones adicionales para otros campos según sea necesario
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $modbus = Modbus::findOrFail($id);
        $modbus->update($request->all());

        return redirect()->route('modbuses.index', $modbus->production_line_id)->with('success', 'Modbus actualizado correctamente.');
    }

    public function destroy($production_line_id, $modbus)
    {
        // Encuentra el modbus y elimínalo
        $modbus = Modbus::findOrFail($modbus);
        $modbus->delete();
    
        return redirect()->route('modbuses.index', $production_line_id)->with('success', 'Modbus eliminado con éxito');
    }
}
