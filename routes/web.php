<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ModualController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginSecurityController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ModbusController;
use Arcanedev\LogViewer\Facades\LogViewer;
use App\Http\Controllers\MonitorOeeController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\RfidController;
use App\Http\Controllers\RfidCategoryController;
use App\Http\Controllers\RfidDeviceController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
//LogViewer::routes();

Route::get('/debug', [DebugController::class, 'index']);

// Ruta para mostrar el formulario de edición
Route::get('customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');

// Ruta para actualizar el cliente
Route::put('customers/{id}', [CustomerController::class, 'update'])->name('customers.update');

// Ruta para eliminar el cliente
Route::delete('customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

// Ruta para obtener los datos de los clientes (para DataTables)
Route::get('customers/getCustomers', [CustomerController::class, 'getCustomers'])->name('customers.getCustomers');

// Ruta para la página principal de clientes
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::resource('customers', CustomerController::class)->except(['edit', 'update', 'destroy']);



// Ruta para la página principal de sensores
Route::get('sensors/{id}', [SensorController::class, 'listSensors'])->name('sensors.index');
// Rutas para smartsensors

// Mostrar el listado de sensores para una línea de producción
Route::get('smartsensors/{production_line_id}', [SensorController::class, 'index'])->name('smartsensors.index');

// Mostrar el formulario para crear un nuevo sensor en una línea de producción
Route::get('smartsensors/{production_line_id}/create', [SensorController::class, 'create'])->name('smartsensors.create');

// Almacenar un nuevo sensor
Route::post('smartsensors/{production_line_id}', [SensorController::class, 'store'])->name('smartsensors.store');

// Mostrar el formulario para editar un sensor existente
Route::get('smartsensors/{sensor}/edit', [SensorController::class, 'edit'])->name('smartsensors.edit');

// Actualizar un sensor existente
Route::put('smartsensors/{sensor}', [SensorController::class, 'update'])->name('smartsensors.update');

// Eliminar un sensor existente
Route::delete('smartsensors/{sensor}', [SensorController::class, 'destroy'])->name('smartsensors.destroy');





// Rutas para las líneas de producción
Route::get('customers/{customer_id}/productionlines', [ProductionLineController::class, 'index'])->name('productionlines.index');
Route::get('productionlines/{id}/edit', [ProductionLineController::class, 'edit'])->name('productionlines.edit');
Route::delete('productionlines/{id}', [ProductionLineController::class, 'destroy'])->name('productionlines.destroy');
Route::get('customers/{customer_id}/productionlinesjson', [ProductionLineController::class, 'getProductionLines'])->name('productionlinesjson.index');
Route::put('productionlines/{id}', [ProductionLineController::class, 'update'])->name('productionlines.update');
Route::get('customers/{customer_id}/productionlinescreate', [ProductionLineController::class, 'create'])->name('productionlines.create');


Route::post('productionlines', [ProductionLineController::class, 'store'])->name('productionlines.store');


//Route::get('barcoders/{production_line_id}', [BarcodeController::class, 'index'])->name('barcoders.index');

// Ruta para listar los barcodes de una línea de producción
Route::get('productionlines/{production_line_id}/barcodes', [BarcodeController::class, 'index'])->name('barcodes.index');

// Ruta para obtener los barcodes en formato JSON
Route::get('productionlines/{production_line_id}/barcodesjson', [BarcodeController::class, 'getBarcodes'])->name('barcodes.json');

// Ruta para mostrar el formulario de creación
Route::get('productionlines/{production_line_id}/barcodes/create', [BarcodeController::class, 'create'])->name('barcodes.create');

// Ruta para almacenar el nuevo barcode
Route::post('productionlines/{production_line_id}/barcodes', [BarcodeController::class, 'store'])->name('barcodes.store');

// Ruta para mostrar el formulario de edición
Route::get('barcodes/{id}/edit', [BarcodeController::class, 'edit'])->name('barcodes.edit');

// Ruta para actualizar el barcode
Route::put('barcodes/{id}', [BarcodeController::class, 'update'])->name('barcodes.update');

// Ruta para eliminar el barcode
Route::delete('barcodes/{id}', [BarcodeController::class, 'destroy'])->name('barcodes.destroy');

//ruta para impresoras
Route::resource('printers', PrinterController::class);

// Ruta para listar los Modbuses de una línea de producción
Route::get('productionlines/{production_line_id}/modbuses', [ModbusController::class, 'index'])->name('modbuses.index');

// Ruta para obtener los Modbuses en formato JSON (opcional, si usas DataTables)
Route::get('productionlines/{production_line_id}/modbusesjson', [ModbusController::class, 'getModbuses'])->name('modbuses.json');

// Ruta para mostrar el formulario de creación
Route::get('productionlines/{production_line_id}/modbuses/create', [ModbusController::class, 'create'])->name('modbuses.create');

// Ruta para almacenar el nuevo Modbus
Route::post('productionlines/{production_line_id}/modbuses', [ModbusController::class, 'store'])->name('modbuses.store');

Route::delete('productionlines/{production_line_id}/modbuses/{modbus}', [ModbusController::class, 'destroy'])->name('modbuses.destroy');

// Ruta para mostrar el formulario de edición
Route::get('modbuses/{id}/edit', [ModbusController::class, 'edit'])->name('modbuses.edit');

// Ruta para actualizar el Modbus
Route::put('modbuses/{id}', [ModbusController::class, 'update'])->name('modbuses.update');

// Ruta para eliminar el Modbus
Route::resource('oee', MonitorOeeController::class);

Route::get('logs', [LogController::class, 'view'])->name('logs.view');




Route::get('/', [HomeController::class, 'index'])->name('home')->middleware(['auth', 'XSS', '2fa']);

Route::post('/chart', [HomeController::class, 'chart'])->name('get.chart.data')->middleware(['auth', 'XSS']);

Route::get('notification', [HomeController::class, 'notification']);

Route::group(['middleware' => ['auth', 'XSS']], function () {
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('permission', PermissionController::class);
    Route::resource('modules', ModualController::class);
});

Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('users.destroy')->middleware(['auth', 'XSS']);

Route::post('/role/{id}', [RoleController::class, 'assignPermission'])->name('roles_permit')->middleware(['auth', 'XSS']);

Route::group(['middleware' => ['auth', 'XSS']], function () {
    Route::get('setting/email-setting', [SettingController::class, 'getmail'])->name('settings.getmail');
    Route::post('setting/email-settings_store', [SettingController::class, 'saveEmailSettings'])->name('settings.emails');

    Route::get('setting/datetime', [SettingController::class, 'getdate'])->name('datetime');
    Route::post('setting/datetime-settings_store', [SettingController::class, 'saveSystemSettings'])->name('settings.datetime');

    Route::get('setting/logo', [SettingController::class, 'getlogo'])->name('getlogo');
    Route::post('setting/logo_store', [SettingController::class, 'store'])->name('settings.logo');
    Route::resource('settings', SettingController::class);

    Route::get('test-mail', [SettingController::class, 'testMail'])->name('test.mail');
    Route::post('test-mail', [SettingController::class, 'testSendMail'])->name('test.send.mail');
});

Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(['auth', 'XSS']);

Route::post('edit-profile', [UserController::class, 'editprofile'])->name('update.profile')->middleware(['auth', 'XSS']);

Route::group(['middleware' => ['auth', 'XSS']], function () {
    Route::get('change-language/{lang}', [LanguageController::class, 'changeLanquage'])->name('change.language');
    Route::get('manage-language/{lang}', [LanguageController::class, 'manageLanguage'])->name('manage.language');
    Route::post('store-language-data/{lang}', [LanguageController::class, 'storeLanguageData'])->name('store.language.data');
    Route::get('create-language', [LanguageController::class, 'createLanguage'])->name('create.language');
    Route::post('store-language', [LanguageController::class, 'storeLanguage'])->name('store.language');
    Route::delete('/lang/{lang}', [LanguageController::class, 'destroyLang'])->name('lang.destroy');
    Route::get('language', [LanguageController::class, 'index'])->name('index');
});

Route::get('generator_builder', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@builder')->name('io_generator_builder')->middleware(['auth', 'XSS']);

Route::get('field_template', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@fieldTemplate')->name('io_field_template')->middleware(['auth', 'XSS']);

Route::get('relation_field_template', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@relationFieldTemplate')->name('io_relation_field_template')->middleware(['auth', 'XSS']);

Route::post('generator_builder/generate', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@generate')->name('io_generator_builder_generate')->middleware(['auth', 'XSS']);

Route::post('generator_builder/rollback', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@rollback')->name('io_generator_builder_rollback')->middleware(['auth', 'XSS']);

Route::post('generator_builder/generate-from-file', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@generateFromFile')->name('io_generator_builder_generate_from_file')->middleware(['auth', 'XSS']);


Route::get('whatsapp/notifications', [App\Http\Controllers\WhatsAppController::class, 'sendNotification'])->name('whatsapp.notifications');
Route::post('whatsapp/disconnect', [App\Http\Controllers\WhatsAppController::class, 'disconnect'])->name('whatsapp.disconnect');
Route::post('whatsapp/update-phone', [App\Http\Controllers\WhatsAppController::class, 'updatePhoneNumber'])->name('whatsapp.updatePhone');
Route::post('whatsapp/send-test-message', [App\Http\Controllers\WhatsAppController::class, 'sendTestMessage'])->name('whatsapp.sendTestMessage');




Route::get('rfid/{production_line_id}', [RfidController::class, 'index'])->name('rfid.index');
Route::get('rfid/create/{production_line_id}', [RfidController::class, 'create'])->name('rfid.create');
Route::post('rfid', [RfidController::class, 'store'])->name('rfid.store');
Route::get('rfid/{id}/edit', [RfidController::class, 'edit'])->name('rfid.edit');
Route::put('rfid/{id}', [RfidController::class, 'update'])->name('rfid.update');
Route::delete('rfid/{id}', [RfidController::class, 'destroy'])->name('rfid.destroy');

Route::prefix('rfid-categories')->group(function () {
    Route::get('/{production_line_id}', [RfidCategoryController::class, 'index'])->name('rfid.categories.index');
    Route::get('/create/{production_line_id}', [RfidCategoryController::class, 'create'])->name('rfid.categories.create');
    Route::post('/', [RfidCategoryController::class, 'store'])->name('rfid.categories.store');
    Route::get('/{id}/edit', [RfidCategoryController::class, 'edit'])->name('rfid.categories.edit');
    Route::put('/{id}', [RfidCategoryController::class, 'update'])->name('rfid.categories.update');
    Route::delete('/{id}', [RfidCategoryController::class, 'destroy'])->name('rfid.categories.destroy');
});

Route::prefix('rfid-devices')->group(function () {
    Route::get('/{production_line_id}', [RfidDeviceController::class, 'index'])->name('rfid.devices.index');
    Route::get('/create/{production_line_id}', [RfidDeviceController::class, 'create'])->name('rfid.devices.create');
    Route::post('/', [RfidDeviceController::class, 'store'])->name('rfid.devices.store');
    Route::get('/{id}/edit', [RfidDeviceController::class, 'edit'])->name('rfid.devices.edit');
    Route::put('/{id}', [RfidDeviceController::class, 'update'])->name('rfid.devices.update');
    Route::delete('/{id}', [RfidDeviceController::class, 'destroy'])->name('rfid.devices.destroy');
});


Route::group(['prefix' => '2fa', 'middleware' => ['auth', 'XSS']], function () {
    Route::get('/', [UserController::class, 'profile'])->name('2fa');
    Route::post('/generateSecret', [LoginSecurityController::class, 'generate2faSecret'])->name('generate2faSecret');
    Route::post('/enable2fa', [LoginSecurityController::class, 'enable2fa'])->name('enable2fa');
    Route::post('/disable2fa', [LoginSecurityController::class, 'disable2fa'])->name('disable2fa');

    // Middleware 2fa
    Route::post('/2faVerify', function () {
        return redirect()->back();
    })->name('2faVerify')->middleware('2fa');
});
