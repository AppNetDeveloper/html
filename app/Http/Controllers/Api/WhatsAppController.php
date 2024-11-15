<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request; // Asegúrate de importar esta clase
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppCredential; // Asegúrate de que este modelo exista
use Illuminate\Support\Facades\Validator; // Asegúrate de importar Validator
use Illuminate\Http\JsonResponse; // Asegúrate de incluir esta línea al inicio del archivo
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WhatsAppController extends Controller
{
    public $nodeApiUrl = 'http://localhost:3005'; // URL de tu API Node

    /**
     * Obtiene el QR como imagen PNG
     */
    public function getQR()
    {
        try {
            $response = Http::get("{$this->nodeApiUrl}/get-qr");
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el código QR'
                ], 404);
            }

            $data = $response->json();

            if (!isset($data['qr']) || !$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'No hay código QR disponible'
                ], 404);
            }

            $qrCode = QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($data['qr']);

            return response($qrCode, 200)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
                ->header('Pragma', 'no-cache');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el código QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el QR como SVG
     */
    public function getQRSvg()
    {
        try {
            $response = Http::get("{$this->nodeApiUrl}/get-qr");
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el código QR'
                ], 404);
            }
    
            $data = $response->json();
    
            if (!isset($data['qr']) || !$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'No hay código QR disponible'
                ], 404);
            }
    
            // Generar el QR en formato SVG y convertirlo a texto
            $qrCode = QrCode::format('svg')
                            ->size(300)
                            ->margin(2)
                            ->generate($data['qr']);
    
            // Convertir el SVG a una cadena de texto antes de retornarlo
            $qrSvgContent = (string) $qrCode;
    
            return response()->json([
                'success' => true,
                'qr_svg' => $qrSvgContent
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el código QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene el QR como base64
     */
    public function getQRBase64()
    {
        try {
            $response = Http::get("{$this->nodeApiUrl}/get-qr");
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el código QR'
                ], 404);
            }
    
            $data = $response->json();
    
            if (!isset($data['qr']) || !$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'No hay código QR disponible'
                ], 404);
            }
    
            // Generar el QR en formato SVG, que no requiere `Imagick`
            $qrCodeSvg = QrCode::format('svg')
                                ->size(300)
                                ->margin(2)
                                ->generate($data['qr']);
    
            // Convertir el SVG a base64
            $qrBase64 = base64_encode($qrCodeSvg);
    
            return response()->json([
                'success' => true,
                'qr_image' => 'data:image/svg+xml;base64,' . $qrBase64
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el código QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    
    public function storeCredentials(Request $request)
    {
        $credentials = WhatsAppCredential::updateOrCreate(
            ['id' => 1],
            [
                'creds' => $request->input('creds'), // Guarda el JSON de `creds` filtrado
                'keys' => $request->input('keys'),   // Guarda el JSON de `keys` (en este caso, `{}`)
            ]
        );
    
        return response()->json(['message' => 'Credenciales guardadas'], 200);
    }
    
    

    public function sendMessage(Request $request)
    {
        // Obtener los parámetros `jid` y `message` desde la solicitud, ya sea GET o POST
        $jid = $request->input('jid');       // Ejemplo: '34619929305@s.whatsapp.net'
        $message = $request->input('message');
    
        // Validar que `jid` y `message` estén presentes
        if (!$jid || !$message) {
            return response()->json(['error' => 'Faltan parámetros necesarios: jid y message'], 400);
        }
    
        // Enviar la solicitud HTTP para enviar el mensaje
        $response = Http::post($this->nodeApiUrl . '/send-message', [
            'jid' => $jid,
            'message' => $message,
        ]);
    
        if ($response->successful()) {
            return response()->json(['message' => 'Mensaje enviado correctamente'], 200);
        } else {
            return response()->json(['error' => 'No se pudo enviar el mensaje'], 500);
        }
    }

    public function logout()
    {
        try {
            // 1. Detener el proceso supervisado
            exec('supervisorctl stop whatsapp-service', $outputStop, $statusStop);
            
            if ($statusStop !== 0) {
                return response()->json(['error' => 'Error al detener el proceso de WhatsApp'], 500);
            }

            // 2. Eliminar el directorio de autenticación
            $authDir = base_path('baileys_auth_info'); // Asegúrate de que el directorio sea correcto
            if (is_dir($authDir)) {
                exec("rm -rf " . escapeshellarg($authDir), $outputRemove, $statusRemove);

                if ($statusRemove !== 0) {
                    return response()->json(['error' => 'Error al eliminar el directorio de autenticación'], 500);
                }
            }

            // 3. Reiniciar el proceso supervisado
            exec('supervisorctl start whatsapp-service', $outputStart, $statusStart);
            
            if ($statusStart !== 0) {
                return response()->json(['error' => 'Error al iniciar el proceso de WhatsApp'], 500);
            }

            return response()->json(['message' => 'Sesión de WhatsApp cerrada y reiniciada correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }


}
