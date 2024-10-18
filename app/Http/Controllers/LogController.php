<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function view(Request $request)
    {
        $logFilePath = storage_path('logs/laravel-tcp-client.out.log');
        $production_line_id = $request->input('production_line_id'); // Asegúrate de recibir este parámetro

        // Verifica si el archivo de log existe
        if (File::exists($logFilePath)) {
            $logs = File::get($logFilePath);
        } else {
            $logs = 'El archivo de log no existe.';
        }

        // Pasa el production_line_id a la vista
        return view('logs.view', compact('logs', 'production_line_id'));
    }
}
