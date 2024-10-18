<?php
namespace App\Http\Controllers;

use App\Models\Barcode;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

use App\Models\ProductionLine;



class BarcodeController extends Controller
{
    public function index($production_line_id)
    {
        return view('barcodes.index', compact('production_line_id'));
    }

    public function getBarcodes(Request $request, $production_line_id, DataTables $dataTables)
    {
        $query = Barcode::where('production_line_id', $production_line_id);

        return DataTables::of($query)
            ->addColumn('action', function ($barcode) {
                $editUrl = route('barcodes.edit', $barcode->id);
                $deleteUrl = route('barcodes.destroy', $barcode->id);
                $csrfToken = csrf_token();

                return "<a href='$editUrl' class='btn btn-sm btn-primary'>Editar</a>
                    <form action='$deleteUrl' method='POST' style='display:inline;'>
                        <input type='hidden' name='_token' value='$csrfToken'>
                        <input type='hidden' name='_method' value='DELETE'>
                        <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"¿Estás seguro?\")'>Eliminar</button>
                    </form>";
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create($production_line_id)
{
    $productionLine = ProductionLine::findOrFail($production_line_id);
    $customer_id = $productionLine->customer_id; // Obtener el customer_id a partir de la línea de producción

    return view('barcodes.create', compact('production_line_id', 'customer_id'));
}


public function store(Request $request, $production_line_id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'mqtt_topic_barcodes' => 'required|string|max:255',
        'machine_id' => 'nullable|string|max:255',
        'ope_id' => 'nullable|string|max:255',
        'order_notice' => 'nullable|json',
        'last_barcode' => 'nullable|string|max:255',
        'iniciar_model' => 'nullable|string|max:255', // Validación del nuevo campo
        'sended' => 'nullable|integer', // Validación del nuevo campo
    ]);

    $token = Str::random(20);

    Barcode::create([
        'production_line_id' => $production_line_id,
        'name' => $request->name,
        'token' => $token,
        'mqtt_topic_barcodes' => $request->mqtt_topic_barcodes,
        'machine_id' => $request->machine_id,
        'ope_id' => $request->ope_id,
        'order_notice' => $request->order_notice,
        'last_barcode' => $request->last_barcode,
        'ip_zerotier' => $request->ip_zerotier,
        'user_ssh' => $request->user_ssh,
        'port_ssh' => $request->port_ssh,
        'user_ssh_password' => $request->user_ssh_password,
        'ip_barcoder' => $request->ip_barcoder,
        'port_barcoder' => $request->port_barcoder,
        'conexion_type' => $request->conexion_type,
        'iniciar_model' => $request->iniciar_model ?? 'INICIAR', // Asignación del nuevo campo
        'sended' => $request->sended ?? 0, // Asignación del nuevo campo
    ]);

    return redirect()->route('barcodes.index', $production_line_id)->with('success', 'Nuevo barcode creado correctamente.');
}


    public function edit($id)
    {
        $barcode = Barcode::findOrFail($id);
        return view('barcodes.edit', compact('barcode'));
        
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mqtt_topic_barcodes' => 'required|string|max:255',
            'machine_id' => 'nullable|string|max:255',
            'ope_id' => 'nullable|string|max:255',
            'order_notice' => 'nullable|json',
            'last_barcode' => 'nullable|string|max:255',
            'iniciar_model' => 'nullable|string|max:255', // Validación del nuevo campo
            'sended' => 'nullable|integer', // Validación del nuevo campo
        ]);
    
        $barcode = Barcode::findOrFail($id);
        $barcode->update([
            'name' => $request->name,
            'mqtt_topic_barcodes' => $request->mqtt_topic_barcodes,
            'machine_id' => $request->machine_id,
            'ope_id' => $request->ope_id,
            'order_notice' => $request->order_notice,
            'last_barcode' => $request->last_barcode,
            'ip_zerotier' => $request->ip_zerotier,
            'user_ssh' => $request->user_ssh,
            'port_ssh' => $request->port_ssh,
            'user_ssh_password' => $request->user_ssh_password,
            'ip_barcoder' => $request->ip_barcoder,
            'port_barcoder' => $request->port_barcoder,
            'conexion_type' => $request->conexion_type,
            'iniciar_model' => $request->iniciar_model ?? 'INICIAR', // Asignación del nuevo campo
            'sended' => $request->sended ?? 0, // Asignación del nuevo campo
        ]);
    
        return redirect()->route('barcodes.index', $barcode->production_line_id)->with('success', 'Barcode actualizado correctamente.');
    }
    
    public function destroy($id)
    {
        $barcode = Barcode::findOrFail($id);
        $barcode->delete();

        return redirect()->route('barcodes.index', $barcode->production_line_id)->with('success', 'Barcode eliminado correctamente.');
    }
}
