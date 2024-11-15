<?php

// ConnectWhatsApp.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ConnectWhatsApp extends Command
{
    protected $signature = 'whatsapp:connect';
    protected $description = 'Conecta a WhatsApp usando Baileys sin generar QR';

    public function handle()
    {
        // Inicia el proceso sin mostrar el QR
        $process = new Process(['node', base_path('whatsapp-client.js')]);
        $process->setTimeout(null);  // Sin límite de tiempo
        $process->start();

        // Muestra los mensajes en la consola
        $process->wait(function ($type, $buffer) {
            echo $buffer;
        });

        return Command::SUCCESS;
    }
}
