<?php

namespace App\Http\Controllers;

use App\Models\Sensor; // Asegúrate de que este 'use' esté presente
use App\Models\Barcode;
use Illuminate\Http\Request;


class SensorController extends Controller
{
    public function index($production_line_id)
    {
        // Obtener los sensores filtrados por el ID de la línea de producción
        $sensors = Sensor::where('production_line_id', $production_line_id)->get();

        // Retornar la vista de smartsensors.index con los sensores filtrados
        return view('smartsensors.index', compact('sensors', 'production_line_id'));
    }

    public function listSensors()
    {
        // Retorna la vista con la lista de sensores
        return view('sensors.index');
    }

        // Mostrar el formulario para crear un nuevo sensor
        public function create($production_line_id)
        {
            // Obtener los barcoders asociados a la línea de producción
            $barcoders = Barcode::where('production_line_id', $production_line_id)->get();
            return view('smartsensors.create', compact('production_line_id', 'barcoders'));

        }
    
        // Almacenar un nuevo sensor
        public function store(Request $request, $production_line_id)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'barcoder_id' => 'required|integer',
                'sensor_type' => 'required|integer',
                'mqtt_topic_sensor' => 'required|string|max:255',
                'mqtt_topic_1' => 'required|string|max:255',
                'optimal_production_time' => 'nullable|numeric|min:0',
                'reduced_speed_time_multiplier' => 'nullable|numeric|min:0',
                'json_api' => 'nullable|string',
                'function_model_0' => 'required|string|max:255',
                'function_model_1' => 'required|string|max:255',
                'count_total' => 'nullable|numeric|min:0',
                'count_total_0' => 'nullable|numeric|min:0',
                'count_total_1' => 'nullable|numeric|min:0',
                'count_shift_0' => 'nullable|numeric|min:0',
                'count_shift_1' => 'nullable|numeric|min:0',
                'count_order_0' => 'nullable|numeric|min:0',
                'count_order_1' => 'nullable|numeric|min:0',
                'invers_sensors' => 'nullable|boolean',
                'downtime_count' => 'nullable|numeric|min:0',
            ]);
    
            // Crear el sensor y asociarlo a la línea de producción
            $sensor = new Sensor($request->all());
            $sensor->production_line_id = $production_line_id;
            $sensor->save();
    
            return redirect()->route('smartsensors.index', $production_line_id)
                             ->with('success', 'Sensor creado exitosamente.');
        }
    
        // Mostrar el formulario para editar un sensor existente
        public function edit($sensor_id)
        {
            // Buscar el sensor por su ID
            $sensor = Sensor::findOrFail($sensor_id);

            // Obtener los barcoders asociados a la línea de producción del sensor
            $barcoders = Barcode::where('production_line_id', $sensor->production_line_id)->get();

            // Retornar la vista con el sensor y los barcoders
            return view('smartsensors.edit', compact('sensor', 'barcoders'));
        }

    
        // Actualizar un sensor existente
        public function update(Request $request, $sensor_id)
        {
            // Validar los datos del formulario
            $request->validate([
                'name' => 'required|string|max:255',
                'barcoder_id' => 'required|integer',
                'sensor_type' => 'required|integer',
                'mqtt_topic_sensor' => 'required|string|max:255',
                'mqtt_topic_1' => 'required|string|max:255',
                'optimal_production_time' => 'nullable|numeric|min:0',
                'reduced_speed_time_multiplier' => 'nullable|numeric|min:0',
                'json_api' => 'nullable|string',
                'function_model_0' => 'required|string|max:255',
                'function_model_1' => 'required|string|max:255',
                'count_total' => 'nullable|numeric|min:0',
                'count_total_0' => 'nullable|numeric|min:0',
                'count_total_1' => 'nullable|numeric|min:0',
                'count_shift_0' => 'nullable|numeric|min:0',
                'count_shift_1' => 'nullable|numeric|min:0',
                'count_order_0' => 'nullable|numeric|min:0',
                'count_order_1' => 'nullable|numeric|min:0',
                'invers_sensors' => 'nullable|boolean',
                'downtime_count' => 'nullable|numeric|min:0',
            ]);
    
            $sensor = Sensor::findOrFail($sensor_id);
            $sensor->update($request->all());
    
            return redirect()->route('smartsensors.index', $sensor->production_line_id)
                             ->with('success', 'Sensor actualizado exitosamente.');
        }
    
        // Eliminar un sensor existente
        public function destroy($sensor_id)
        {
            $sensor = Sensor::findOrFail($sensor_id);
            $production_line_id = $sensor->production_line_id;
            $sensor->delete();
    
            return redirect()->route('smartsensors.index', $production_line_id)
                             ->with('success', 'Sensor eliminado exitosamente.');
        }
        
    }