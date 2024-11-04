<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ConnectWhatsApp extends Command
{
    protected $signature = 'whatsapp:connect';
    protected $description = 'Conecta a WhatsApp usando Baileys y genera el QR';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Ejecuta el script Node.js que inicia Baileys
        $process = new Process(['node', base_path('whatsapp-client.js')]);
        $process->setTimeout(null);  // Sin límite de tiempo
        $process->start();

        // Captura el código QR
        $process->wait(function ($type, $buffer) {
            if (str_contains($buffer, 'qr:')) {
                // Extrae el QR y guarda en el almacenamiento público
                $qr = str_replace('qr:', '', $buffer);
                Storage::put('public/whatsapp-qr.txt', $qr);
            }
            echo $buffer;
        });

        return Command::SUCCESS;
    }
}
