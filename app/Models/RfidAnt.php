<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RfidAnt extends Model
{
    protected $table = 'rfid_ants';

    protected $fillable = [
        'name',
        'production_line_id',
        'mqtt_topic',
        'token'
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    // Sobrescribimos el método boot para añadir los observadores de eventos
    protected static function boot()
    {
        parent::boot();

        // Observador para reiniciar Supervisor al crear un registro
        static::created(function ($model) {
            self::restartSupervisor();
        });

        // Observador para reiniciar Supervisor si el campo mqtt_topic cambia al actualizar
        static::updated(function ($model) {
            if ($model->isDirty('mqtt_topic')) {
                self::restartSupervisor();
            }
        });

        // Observador para reiniciar Supervisor al eliminar un registro
        static::deleted(function ($model) {
            self::restartSupervisor();
        });
    }

    // Método para reiniciar Supervisor
    protected static function restartSupervisor()
    {
        try {
            Artisan::call('supervisor:restart');
            Log::info('Supervisor restarted successfully due to changes in BluetoothAnt.');
        } catch (\Exception $e) {
            Log::error('Failed to restart Supervisor: ' . $e->getMessage());
        }
    }
}
