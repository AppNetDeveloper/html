<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use Illuminate\Support\Facades\Log;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;

class TcpClient extends Command
{
    protected $signature = 'tcp:client';
    protected $description = 'Connect to multiple TCP servers and read messages continuously';

    protected $processes = [];          // Almacenar los PID de procesos
    protected $connections = [];        // Almacenar conexiones activas por barcode
    protected $connectionStates = [];   // Almacenar estados de conexión de cada barcode
    protected $previousBarcodes = [];   // Almacenar barcodes previos

    protected $failedAttempts = []; // Almacenar intentos fallidos de cada barcode


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->terminateAllProcesses();

        // Inicializar el bucle principal
        while (true) {
            // Obtener todos los barcodes de la base de datos
            $barcodes = Barcode::all();

            // Comprobar cambios en los barcodes
            $this->checkForChanges($barcodes);

            // Esperar un tiempo antes de volver a verificar
            sleep(1); // Esperar 10 segundos antes de la próxima verificación
        }
    }

    private function terminateAllProcesses()
    {
        foreach ($this->processes as $id => $pid) {
            if (isset($this->failedAttempts[$id]) && $this->failedAttempts[$id] >= 3) {
                $this->info("Stopping TCP client for barcode ID: $id due to repeated failures");
                if (posix_kill($pid, SIGTERM)) {
                    unset($this->processes[$id]);
                    unset($this->connections[$id]);
                    unset($this->connectionStates[$id]);
                    unset($this->failedAttempts[$id]);
                    $this->info("Successfully stopped process for barcode ID: $id");
                } else {
                    $this->error("Failed to stop process for barcode ID: $id with PID: $pid");
                }
            }
        }
    }
    

    protected function checkForChanges($currentBarcodes)
    {
        $currentIds = $currentBarcodes->pluck('id')->toArray();
        $previousIds = array_keys($this->processes);

        // Encontrar IDs que han sido eliminados
        $removedIds = array_diff($previousIds, $currentIds);
        foreach ($removedIds as $id) {
            $this->stopProcess($id);
        }

        // Encontrar o iniciar procesos para nuevos barcodes y reiniciar los procesos si hay cambios
        foreach ($currentBarcodes as $barcode) {
            if (!isset($this->processes[$barcode->id])) {
                // Iniciar proceso si no existe
                $this->startProcess($barcode);
            } elseif ($this->hasBarcodeChanged($barcode)) {
                // Si hay cambios, reiniciar proceso
                $this->stopProcess($barcode->id);
                $this->startProcess($barcode);
            }
        }
    }

    protected function hasBarcodeChanged($barcode)
    {
        if (!isset($this->connections[$barcode->id])) {
            return true;
        }

        $previousConnection = $this->connections[$barcode->id];
        $currentIp = $barcode->conexion_type == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
        $currentPort = $barcode->port_barcoder;

        return $previousConnection['ip'] !== $currentIp || $previousConnection['port'] !== $currentPort;
    }

    protected function stopProcess($id)
    {
        if (isset($this->processes[$id])) {
            $this->info("Stopping TCP client for barcode ID: $id");
            if (posix_kill($this->processes[$id], SIGTERM)) {
                unset($this->processes[$id]);
                unset($this->connections[$id]);
                unset($this->connectionStates[$id]);
                $this->info("Successfully stopped process for barcode ID: $id");
            } else {
                $this->error("Failed to stop process for barcode ID: $id with PID: {$this->processes[$id]}");
            }
        }
    }

    protected function startProcess($barcode)
    {
        $ip = $barcode->conexion_type == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
        $port = $barcode->port_barcoder;

        if (empty($ip) || empty($port)) {
            $this->info("Ignoring TCP client for barcode ID: {$barcode->id} due to empty IP or port.");
            return;
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->error("Error al crear un proceso hijo para barcode ID: {$barcode->id}");
        } elseif ($pid) {
            // Proceso padre
            $this->processes[$barcode->id] = $pid; // Guardar el PID del proceso hijo
            $this->connections[$barcode->id] = [
                'ip' => $ip,
                'port' => $port,
            ];
        } else {
            // Proceso hijo
            $this->handleBarcode($barcode);
            exit(0); // Terminar el proceso hijo después de su tarea
        }
    }

    protected function handleBarcode($barcode)
    {
        $conexionType = $barcode->conexion_type;
    
        if ($conexionType == 0) {
            $this->info("No TCP connection will be made for barcode ID {$barcode->id}.");
            return;
        }
    
        $host = $conexionType == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
        $port = $barcode->port_barcoder;
    
        if (empty($host) || empty($port)) {
            $this->info("Ignoring TCP client for barcode ID: {$barcode->id} due to empty IP or port.");
            return;
        }
    
        // Evitar múltiples conexiones simultáneas
        if (isset($this->connectionStates[$barcode->id]) && $this->connectionStates[$barcode->id] === true) {
            $this->info("Ya existe una conexión activa para barcode ID {$barcode->id}, evitando conexión duplicada.");
            return;
        }
    
        // Marcar la conexión como activa
        $this->connectionStates[$barcode->id] = true;
        $this->failedAttempts[$barcode->id] = 0; // Reiniciar los intentos fallidos para este barcode
    
        while (true) {
            $this->info("Connecting to TCP server at $host:$port for barcode ID {$barcode->id}");
    
            // Crear un socket TCP/IP
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                $this->error("Error al crear el socket: " . socket_strerror(socket_last_error()));
                $this->failedAttempts[$barcode->id]++;
                sleep(5);
                continue;
            }
    
            // Conectar al servidor TCP
            $result = @socket_connect($socket, $host, $port); // Usar @ para silenciar advertencias
            if ($result === false) {
                $errorCode = socket_last_error($socket);
                $this->error("Error al conectar al servidor: " . socket_strerror($errorCode) . " (Código de error: $errorCode)");
                socket_close($socket); // Cerrar el socket en caso de error
                $this->failedAttempts[$barcode->id]++;
    
                // Remover la condición que detiene el proceso tras 3 intentos fallidos
                sleep(5);
                continue;
            }
    
            // Conexión exitosa
            $this->info("Conectado al servidor TCP en $host:$port para barcode ID {$barcode->id}");
            $this->failedAttempts[$barcode->id] = 0; // Reiniciar los intentos fallidos si la conexión tiene éxito
    
            // Bucle para leer mensajes continuamente
            while (true) {
                $response = @socket_read($socket, 2048, PHP_NORMAL_READ);
                if ($response === false) {
                    $this->error("Error al leer del servidor: " . socket_strerror(socket_last_error($socket)));
                    break; // Salir del bucle si hay error y reconectar después
                }
    
                // Verifica si el servidor ha cerrado la conexión
                if ($response === '') {
                    $this->info("El servidor ha cerrado la conexión para barcode ID {$barcode->id}");
                    break; // Salir para intentar reconectar
                }
    
                $this->info("Mensaje recibido del servidor para barcode ID {$barcode->id}: $response");
    
                // Procesar el mensaje recibido si no está vacío
                if (trim($response) !== '') {
                    $this->processMessage($barcode, $response);
                }
            }
    
            // Cerrar el socket antes de intentar reconectar
            socket_close($socket);
            $this->info("Intentando reconectar en 5 segundos para barcode ID {$barcode->id}...");
            sleep(5);
        }
    
        // Marcar la conexión como cerrada al salir del bucle (en caso de terminación controlada)
        $this->connectionStates[$barcode->id] = false;
    }
    
    

    protected function processMessage($barcode, $message)
    {
        $this->info("Processing message for barcode ID {$barcode->id} : $message");

        if (trim($message) === '') {
            //$this->info("Mensaje vacío para barcode ID {$barcode->id}, ignorando.");
            return;
        }

        $this->handleMqttCommands($barcode->id, $message);
    }


    protected function handleMqttCommands($id, $barcodeValue)
    {
       // $this->info("estoy aqui {$id}: $barcodeValue");
        $barcode = Barcode::where('id', (int)$id)->first();
        $mqttTopicBase = $barcode->mqtt_topic_barcodes;
        $mqttTopicBarcodes = $mqttTopicBase ."/prod_order_mac";
       // $this->info("mqtt topic : " . $mqttTopicBarcodes);
        $mqttTopicOrders = $mqttTopicBase ."/prod_order_notice";
        $mqttTopicFinish = $mqttTopicBase ."/order_finish";
        $mqttTopicPause = $mqttTopicBase ."/order_pause";
        $opeId = $barcode->ope_id;
        $orderNotice = $barcode->order_notice;
        $lastBarcode = $barcode->last_barcode;
        $machineId = $barcode->machine_id;
        $mqttTopicShift = $mqttTopicBase ."/shift";
        $mqttTopicNext = $mqttTopicBase ."/prod_order_notice_next";
        $iniciarModel = $barcode->iniciar_model;

        $orderNoticeData = json_decode($orderNotice, true);
        $orderId = $orderNoticeData['orderId'] ?? null;

        $comando = [];
        $mqttTopic = null; // Initialize to null to avoid errors
        $barcodeValue = trim($barcodeValue);

        if (in_array($lastBarcode, ['FINALIZAR', 'PAUSAR', null, '']) && $barcodeValue === $iniciarModel) {
            // Case 1: lastBarcode is FINALIZAR, PAUSAR, NULL, or empty, and barcodeValue is INICIAR
            //primero llamamos a obtener el orderid pero comprobamos si $iniciarModel es INICIAR o INICIAR-2 si es 2 tenemos que buscar todos los 
            if ($iniciarModel === 'INICIAR') {
                // Buscar todas las líneas que tienen el mismo `mqtt_topic_barcodes` con el valor `$mqttTopicBase`
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí

                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();
                    $this->info("Puesto en modo escucha" );
                }
                $nowDateTime = date('Y-m-d H:i:s');

                //ahorra preguntamos el next orderid
                $this->sendNextOrder($barcode->machine_id, $mqttTopicNext);
                 // Re actualizar el last_barcode
                $barcodenew = $this->waitTimeNow($id, $nowDateTime);
                //actualizar el OrderId
                $updatedOrderId = $this->orderIdNew($barcodenew);
                
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                   //ahorra mandamos el mac
                    $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                }
            }

            if ($iniciarModel === 'INICIAR-2') {
                // Buscar todas las líneas que tienen el mismo `mqtt_topic_barcodes` con el valor `$mqttTopicBase`
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí
                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();

                    $nowDateTime = date('Y-m-d H:i:s');
                    //ahorra preguntamos el next orderid
                    $this->sendNextOrder($relatedBarcode->machine_id, $mqttTopicNext);
                    // Re actualizar el last_barcode
                    $barcodenew = $this->waitTimeNow($relatedBarcode->id, $nowDateTime);
                    //actualizar el OrderId
                    $updatedOrderId = $this->orderIdNew($barcodenew);
                    //ahorra preguntamos el next orderid
                    $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                }
            }
            
            
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === $iniciarModel) {

            if ($iniciarModel === 'INICIAR') {
                // Buscar todas las líneas que tienen el mismo `mqtt_topic_barcodes` con el valor `$mqttTopicBase`
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)->get();
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    //ahorra mandamos el mac
                    // Re actualizar el last_barcode
                    $barcoderLatest = $this->barcoderLatest($relatedBarcode->id,);
                    //actualizar el OrderId
                    $updatedOrderIdLatest = $this->orderIdNew($barcoderLatest);

                    $this->sendOrderMac("1",$updatedOrderIdLatest,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();
                    $this->info("Puesto en modo escucha" );
                }
                //ahorra preguntamos el next orderid

                $nowDateTime = date('Y-m-d H:i:s');
                //ahorra preguntamos el next orderid
                $this->sendNextOrder($relatedBarcode->machine_id, $mqttTopicNext);
                // Re actualizar el last_barcode
                $barcodenew = $this->waitTimeNow($relatedBarcode->id, $nowDateTime);
                $updatedOrderId = $this->orderIdNew($barcodenew);
                
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                   //ahorra mandamos el mac
                    $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                }
            }
            if ($iniciarModel === 'INICIAR-2') {
                $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("mando mqtt a: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    //ahorra mandamos el mac
                    // Re actualizar el last_barcode
                    $barcoderLatest = $this->barcoderLatest($relatedBarcode->id,);
                    //actualizar el OrderId
                    $updatedOrderIdLatest = $this->orderIdNew($barcoderLatest);

                    $this->sendOrderMac("1",$updatedOrderIdLatest,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                    $relatedBarcode->sended = 1;
                    $relatedBarcode->last_barcode = "INICIAR";
                    $relatedBarcode->save();

                    $nowDateTime = date('Y-m-d H:i:s');
                    //ahorra preguntamos el next orderid
                    $this->sendNextOrder($relatedBarcode->machine_id, $mqttTopicNext);
                    // Re actualizar el last_barcode
                    $barcodenew = $this->waitTimeNow($relatedBarcode->id, $nowDateTime);
                    //actualizar el OrderId
                    $updatedOrderId = $this->orderIdNew($barcodenew);
                    //ahorra preguntamos el next orderid
                    $this->sendOrderMac("0",$updatedOrderId,$relatedBarcode->machine_id, $mqttTopicBarcodes);
                }
                
            }
            
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'FINALIZAR') {
            // Case 3: lastBarcode is INICIAR and barcodeValue is FINALIZAR
            $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí
                    // Re actualizar el last_barcode
                    $updatedOrderNotice = json_decode($relatedBarcode->order_notice, true);
                    $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;
                    $comando = [
                        "orderId" => $updatedOrderId
                    ];
                    $this->publishMqttMessage($mqttTopicFinish, $comando);
        
                    $relatedBarcode->last_barcode = "FINALIZAR";
                    $relatedBarcode->save();
                }
            
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'PAUSAR') {
            // Case 4: lastBarcode is INICIAR and barcodeValue is PAUSAR
            $relatedBarcodes = Barcode::where('mqtt_topic_barcodes', $mqttTopicBase)->get();
    
                foreach ($relatedBarcodes as $relatedBarcode) {
                    // Aquí puedes realizar cualquier acción que necesites con cada barcode relacionado
                    $this->info("Encontrada línea relacionada con ID: {$relatedBarcode->id} y valor de iniciar_model: {$relatedBarcode->iniciar_model}");
                    // Si necesitas algo específico de cada barcode relacionado, puedes trabajar con $relatedBarcode aquí
                    // Re actualizar el last_barcode
                    $updatedOrderNotice = json_decode($relatedBarcode->order_notice, true);
                    $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;
                    $comando = [
                        "orderId" => $updatedOrderId
                    ];
                    $this->publishMqttMessage($mqttTopicPause, $comando);
        
                    $relatedBarcode->last_barcode = "PAUSAR";
                    $relatedBarcode->save();
                }
        } elseif ($barcodeValue === 'Turno Programado') {
            // Case 5: barcodeValue is Turno Programado
            $comando = [
                "shift_type" => "Turno Programado",
                "event" => "start"
            ];
            $this->publishMqttMessage($mqttTopicShift, $comando);
        }
    }

    private function waitTimeNow($id, $dataTime)
    {
        $maxRetries = 20; // Define un número máximo de reintentos para evitar ciclos infinitos
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            usleep(500000); // Espera de 0.5 segundos

            $barcodenew = Barcode::find($id);

            // Verificar si $barcodenew no es null y tiene un valor para `updated_at`
            if ($barcodenew && !is_null($barcodenew->updated_at)) {
                // Si el barcode tiene un `updated_at` más nuevo, retornar la línea actualizada
                if ($barcodenew->updated_at > $dataTime) {
                    $this->info("El barcode ya está actualizado: " . date('Y-m-d H:i:s', strtotime($barcodenew->updated_at)));
                    return $barcodenew;
                } else {
                    $this->info("vuelvo a buscar en un segundo: " . date('Y-m-d H:i:s'));
                }
            } else {
                $this->info("El campo `updated_at` es null o no se encontró un barcode con ID: {$id}, reintentando...");
            }

            $retryCount++;
        }
        // Si alcanzamos el máximo de reintentos sin cumplir la condición, retornamos la línea encontrada (aunque sin cambios).
        $this->error("No se pudo actualizar el barcode después de varios intentos.");
        return $barcodenew;
    }

    private function barcoderLatest($id)
    {
            $barcodenew = Barcode::find($id);
            return $barcodenew;
    }
    
    
    private function orderIdNew($barcodenew)
    {
        $updatedOrderNotice = json_decode($barcodenew->order_notice, true);
        $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;
        

        $this->info("actualizo json de la db: " . $updatedOrderId);
        return $updatedOrderId;
    }

    private function sendNextOrder($machineId, $mqttTopic)
    {
        $comando = [
            "machineId" => $machineId,
            "time" => date('Y-m-d H:i:s'),
        ];

        $this->publishMqttMessage($mqttTopic, $comando);
        $this->info("mesaje enviado : ");
    }

    private function sendOrderMac($action,$updatedOrderId,$machineId, $mqttTopicBarcodes)
    {
                $comando = [
                    "action" => $action,
                    "orderId" => $updatedOrderId,
                    "machineId" => $machineId,
                    "opeId" => "ENVASADO"
                ];
                $mqttTopic = $mqttTopicBarcodes;
                $this->publishMqttMessage($mqttTopic, $comando);
    }
    // Funciones mqtt
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Inserta en la tabla mqtt_send_server1
           MqttSendServer2::createRecord($topic, $message);
           usleep(1000000);
            // Inserta en la tabla mqtt_send_server2
            MqttSendServer1::createRecord($topic, $message);
            
    
            $this->info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.".$topic);
    
            } catch (\Exception $e) {
                Log::error("Error storing message in databases: " . $e->getMessage());
            }
    }
}
