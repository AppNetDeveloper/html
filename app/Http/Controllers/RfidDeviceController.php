<?php

namespace App\Http\Controllers;

use App\Models\RfidDetail;
use App\Models\RfidReading;
use App\Models\RfidAnt;
use Illuminate\Http\Request;

class RfidDeviceController extends Controller
{
    /**
     * Muestra la lista de dispositivos RFID asociados a una línea de producción.
     */
    public function index($production_line_id)
    {
        $rfidDevices = RfidDetail::where('production_line_id', $production_line_id)->get();

        return view('rfid.devices.index', [
            'production_line_id' => $production_line_id,
            'rfidDevices' => $rfidDevices,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo dispositivo RFID.
     */
    public function create($production_line_id)
    {
        $rfidReadings = RfidReading::where('production_line_id', $production_line_id)->get();
        $rfidAnts = RfidAnt::where('production_line_id', $production_line_id)->get();

        return view('rfid.devices.form', [
            'rfidDevice' => new RfidDetail(),
            'rfidReadings' => $rfidReadings,
            'rfidAnts' => $rfidAnts,
            'production_line_id' => $production_line_id,
        ]);
    }

    /**
     * Almacena un nuevo dispositivo RFID en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|unique:rfid_details,token|max:255',
            'rfid_reading_id' => 'required|exists:rfid_readings,id',
            'rfid_ant_id' => 'required|exists:rfid_ants,id',
            'epc' => 'required|string|max:255',
            'tid' => 'required|string|unique:rfid_details,tid|max:255',
            'serialno' => 'nullable|string|max:255',
            'rssi' => 'nullable|integer',
            'rfid_type' => 'required|integer',
            'count_total' => 'nullable|integer',
            'count_total_0' => 'nullable|integer',
            'count_total_1' => 'nullable|integer',
            'count_shift_0' => 'nullable|integer',
            'count_shift_1' => 'nullable|integer',
            'count_order_0' => 'nullable|integer',
            'count_order_1' => 'nullable|integer',
            'mqtt_topic_1' => 'nullable|string|max:255',
            'function_model_0' => 'nullable|string|max:255',
            'function_model_1' => 'nullable|string|max:255',
            'unic_code_order' => 'nullable|string|max:255',
            'shift_type' => 'nullable|string|max:255',
            'event' => 'nullable|string|max:255',
            'downtime_count' => 'nullable|integer',
            'optimal_production_time' => 'nullable|integer',
            'reduced_speed_time_multiplier' => 'nullable|integer',
            'send_alert' => 'nullable|boolean',
            'search_out' => 'nullable|boolean',
            'last_ant_detect' => 'nullable|string|max:255',
            'last_status_detect' => 'nullable|string|max:255',
        ]);

        RfidDetail::create($request->all());

        return redirect()->route('rfid.devices.index', $request->production_line_id)
            ->with('status', __('Dispositivo RFID creado exitosamente.'));
    }

    /**
     * Muestra el formulario para editar un dispositivo RFID existente.
     */
    public function edit($id)
    {
        $rfidDevice = RfidDetail::findOrFail($id);
        $rfidReadings = RfidReading::where('production_line_id', $rfidDevice->production_line_id)->get();
        $rfidAnts = RfidAnt::where('production_line_id', $rfidDevice->production_line_id)->get();

        return view('rfid.devices.form', [
            'rfidDevice' => $rfidDevice,
            'rfidReadings' => $rfidReadings,
            'rfidAnts' => $rfidAnts,
            'production_line_id' => $rfidDevice->production_line_id,
        ]);
    }

    /**
     * Actualiza un dispositivo RFID existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $rfidDevice = RfidDetail::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|max:255|unique:rfid_details,token,' . $rfidDevice->id,
            'rfid_reading_id' => 'required|exists:rfid_readings,id',
            'rfid_ant_id' => 'required|exists:rfid_ants,id',
            'epc' => 'required|string|max:255',
            'tid' => 'required|string|max:255|unique:rfid_details,tid,' . $rfidDevice->id,
            'serialno' => 'nullable|string|max:255',
            'rssi' => 'nullable|integer',
            'rfid_type' => 'required|integer',
            'count_total' => 'nullable|integer',
            'count_total_0' => 'nullable|integer',
            'count_total_1' => 'nullable|integer',
            'count_shift_0' => 'nullable|integer',
            'count_shift_1' => 'nullable|integer',
            'count_order_0' => 'nullable|integer',
            'count_order_1' => 'nullable|integer',
            'mqtt_topic_1' => 'nullable|string|max:255',
            'function_model_0' => 'nullable|string|max:255',
            'function_model_1' => 'nullable|string|max:255',
            'unic_code_order' => 'nullable|string|max:255',
            'shift_type' => 'nullable|string|max:255',
            'event' => 'nullable|string|max:255',
            'downtime_count' => 'nullable|integer',
            'optimal_production_time' => 'nullable|integer',
            'reduced_speed_time_multiplier' => 'nullable|integer',
            'send_alert' => 'nullable|boolean',
            'search_out' => 'nullable|boolean',
            'last_ant_detect' => 'nullable|string|max:255',
            'last_status_detect' => 'nullable|string|max:255',
        ]);

        $rfidDevice->update($request->all());

        return redirect()->route('rfid.devices.index', $rfidDevice->production_line_id)
            ->with('status', __('Dispositivo RFID actualizado exitosamente.'));
    }

    /**
     * Elimina un dispositivo RFID de la base de datos.
     */
    public function destroy($id)
    {
        $rfidDevice = RfidDetail::findOrFail($id);
        $productionLineId = $rfidDevice->production_line_id;

        $rfidDevice->delete();

        return redirect()->route('rfid.devices.index', $productionLineId)
            ->with('status', __('Dispositivo RFID eliminado exitosamente.'));
    }
}
