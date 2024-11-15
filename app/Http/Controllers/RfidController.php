<?php

namespace App\Http\Controllers;

use App\Models\RfidAnt;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    /**
     * Muestra la lista de antenas RFID asociadas a la línea de producción.
     */
    public function index($production_line_id)
    {
        $rfidAnts = RfidAnt::where('production_line_id', $production_line_id)->get();

        return view('rfid.index', [
            'production_line_id' => $production_line_id,
            'rfidAnts' => $rfidAnts,
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva antena RFID.
     */
    public function create($production_line_id)
    {
        return view('rfid.form', [
            'rfidAnt' => new RfidAnt(),
            'production_line_id' => $production_line_id,
        ]);
    }

    /**
     * Almacena una nueva antena RFID en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mqtt_topic' => 'required|string|max:255',
            'token' => 'required|string|unique:rfid_ants,token|max:255',
            'production_line_id' => 'required|exists:production_lines,id',
        ]);

        RfidAnt::create($request->all());

        return redirect()->route('rfid.index', $request->production_line_id)
            ->with('status', __('Antena RFID creada exitosamente.'));
    }

    /**
     * Muestra el formulario para editar una antena RFID existente.
     */
    public function edit($id)
    {
        $rfidAnt = RfidAnt::findOrFail($id);

        return view('rfid.form', [
            'rfidAnt' => $rfidAnt,
            'production_line_id' => $rfidAnt->production_line_id,
        ]);
    }

    /**
     * Actualiza una antena RFID existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $rfidAnt = RfidAnt::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'mqtt_topic' => 'required|string|max:255',
            'token' => 'required|string|max:255|unique:rfid_ants,token,' . $rfidAnt->id,
        ]);

        $rfidAnt->update($request->all());

        return redirect()->route('rfid.index', $rfidAnt->production_line_id)
            ->with('status', __('Antena RFID actualizada exitosamente.'));
    }

    /**
     * Elimina una antena RFID de la base de datos.
     */
    public function destroy($id)
    {
        $rfidAnt = RfidAnt::findOrFail($id);
        $productionLineId = $rfidAnt->production_line_id;

        $rfidAnt->delete();

        return redirect()->route('rfid.index', $productionLineId)
            ->with('status', __('Antena RFID eliminada exitosamente.'));
    }
}
