<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log; // Agrega esta línea para usar Log


class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function getCustomers(Request $request, DataTables $dataTables)
    {
        // Aquí construyes tu consulta para DataTables
        $query = Customer::query();

        return DataTables::of($query)
            ->addColumn('action', function ($customer) {
                // Aquí construyes las acciones de la columna 'action'
                $editUrl = route('customers.edit', $customer->id);
                $productionLinesUrl = route('productionlines.index', $customer->id);
                $deleteUrl = route('customers.destroy', $customer->id);
                $csrfToken = csrf_token();
                $liveViewUrl = url('/live-weight/index.html?token=' . $customer->token);

                return "<a href='$editUrl' class='btn btn-sm btn-primary'>Editar</a>
                        <a href='$productionLinesUrl' class='btn btn-sm btn-info'>Lineas produccion</a>
                    <form action='$deleteUrl' method='POST' style='display:inline;'>
                        <input type='hidden' name='_token' value='$csrfToken'>
                        <input type='hidden' name='_method' value='DELETE'>
                        <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"¿Estás seguro?\")'>Eliminar</button>
                    </form>
                     <a href='{$liveViewUrl}' class='btn btn-sm btn-primary'>Live weight</a>
                     <a href='/live-sensor/' class='btn btn-sm btn-primary'>Live sensors</a>";
            })
            ->rawColumns(['action']) // Asegúrate de marcar la columna 'action' como raw (HTML)
            ->make(true); // Importante usar 'make(true)' para devolver la respuesta JSON adecuada
    }

     

    public function testCustomers()
    {
        $customers = Customer::all();
        dd($customers); 
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token_zerotier' => 'required|string|max:255',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update($request->all());

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token_zerotier' => 'required|string|max:255',
        ]);

        // Generar un token único
        $token = bin2hex(random_bytes(16));

        Customer::create([
            'name' => $request->name,
            'token_zerotier' => $request->token_zerotier,
            'token' => $token, // Asignar token único
        ]);

        return redirect()->route('customers.index')->with('success', 'Cliente creado con éxito.');
    }

}
