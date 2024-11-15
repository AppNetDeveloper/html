<?php

use App\Http\Controllers\Api\GetTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiBarcoderController;
use App\Http\Controllers\Api\ControlWeightController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\ModbusController;
use App\Http\Controllers\Api\StoreQueueController;
use App\Http\Controllers\Api\ZerotierIpBarcoderController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\OrderStatsController;
use App\Http\Controllers\Api\ScadaController;
use App\Http\Controllers\Api\ScadaMaterialTypeController;
use App\Http\Controllers\Api\RfidDetailController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\BluetoothDetailController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::match(['get', 'post'], '/barcode', [ApibarcoderController::class, 'barcode']);
Route::match(['get', 'post'], '/ip-zerotier', [ZerotierIpBarcoderController::class, 'ipZerotier']);



Route::match(['get', 'post'], '/queue-print', [StoreQueueController::class, 'storeQueuePrint']);
Route::match(['get', 'post'], '/queue-print-list', [StoreQueueController::class, 'getQueuePrints']);

Route::get('/modbuses', [ModbusController::class, 'getModbuses']);

Route::get('/control-weights/{token}/all', [ControlWeightController::class, 'getAllDataByToken']);

Route::middleware(['throttle:1000,1'])->group(function () {
    Route::get('/control-weight/{token}', [ControlWeightController::class, 'getDataByToken']);
});


// Ruta para GET request
Route::get('/order-notice/{token?}', [ApibarcoderController::class, 'getOrderNotice']);

// Ruta para POST request
Route::post('/order-notice', [ApibarcoderController::class, 'getOrderNotice']);


// Ruta para GET request (con el token en la URL)
Route::get('/barcode-info/{token}', [ApiBarcoderController::class, 'getBarcodeInfo']);

// Ruta para POST request (con el token en el cuerpo de la solicitud)
Route::post('/barcode-info', [ApiBarcoderController::class, 'getBarcodeInfo']);

Route::match(['get', 'post'], '/production-lines/{customerToken}', [GetTokenController::class, 'getProductionLinesByCustomerToken']);
Route::match(['get', 'post'], '/modbus-info/{token}', [GetTokenController::class, 'getModbusInfo']);
Route::match(['get', 'post'], '/barcode-info-by-customer/{customerToken}', [GetTokenController::class, 'getBarcodeInfoByCustomer']);
Route::match(['get', 'post'], '/sensors/{token}', [SensorController::class, 'getByToken']);
Route::match(['get', 'post'], '/sensors', [SensorController::class, 'getAllSensors']);

Route::post('/sensor-insert', [SensorController::class, 'sensorInsert']);

Route::match(['get', 'post'], '/order-stats', [OrderStatsController::class, 'getLastOrderStat']);

Route::match(['get', 'post'], '/order-stats-all', [OrderStatsController::class, 'getOrderStatsBetweenDates']);


Route::get('scada/{token}', [ScadaController::class, 'getModbusesByScadaToken']);

Route::get('scada-material/{token}', [ScadaMaterialTypeController::class, 'getScadaMaterialByToken']);

Route::put('/modbus/{modbusId}/material', [ScadaController::class, 'updateMaterialForModbus']);

Route::post('/modbus/send', [ModbusController::class, 'sendDosage']);

Route::post('/modbus/zero', [ModbusController::class, 'setZero']);


//api rfid
Route::post('/rfid-insert', [RfidDetailController::class, 'store']);
Route::get('/rfid-history', [RfidDetailController::class, 'getHistoryRfid']);
Route::get('/get-filters', [RfidDetailController::class, 'getFilters']);

//whatsapp api


Route::post('/whatsapp-credentials', [WhatsAppController::class, 'storeCredentials']);
Route::match(['get', 'post'], '/send-message', [WhatsAppController::class, 'sendMessage']);
Route::match(['get', 'post'], '/whatsapp/logout', [WhatsAppController::class, 'logout']);
Route::get('/whatsapp-qr', [WhatsAppController::class, 'getQR']);
// Retorna el QR como SVG
Route::get('/whatsapp-qr/svg', [WhatsAppController::class, 'getQRSvg']);
// Retorna el QR como base64
Route::get('/whatsapp-qr/base64', [WhatsAppController::class, 'getQRBase64']);



// ruta scanner
Route::prefix('bluetooth')->group(function () {
    // Ruta para insertar un nuevo registro de lectura de Bluetooth
    Route::post('/insert', [BluetoothDetailController::class, 'store'])->name('bluetooth.insert');

    // Ruta para obtener el historial de lecturas de Bluetooth con filtros
    Route::get('/history', [BluetoothDetailController::class, 'getHistoryBluetooth'])->name('bluetooth.history');

    // Ruta para obtener los filtros disponibles para Bluetooth (antenas y MACs)
    Route::get('/filters', [BluetoothDetailController::class, 'getFilters'])->name('bluetooth.filters');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
























































































































































































































































































































































































