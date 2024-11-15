<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RfidReading extends Model
{
    use HasFactory;

    protected $table = 'rfid_readings';

    protected $fillable = [
        'name',              // Nombre o ubicación del dispositivo RFID
        'epc',               // Identificador único del grupo RFID
        'token',             // Token único de la lectura o dispositivo
        'production_line_id', // ID de la línea de producción
    ];

    protected $casts = [
        'data' => 'array',   // Castea 'data' a un arreglo JSON
    ];

    /**
     * Relación con la tabla ProductionLine.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con la tabla Modbus.
     */
    public function modbuses()
    {
        return $this->hasMany(Modbus::class);
    }

    /**
     * Relación con la tabla Barcode.
     */
    public function barcoder()
    {
        return $this->belongsTo(Barcode::class, 'barcoder_id');
    }

    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();

        // Generar un token único antes de crear el registro
        static::creating(function ($rfidReading) {
            $rfidReading->token = Str::uuid(); // O Str::random(32) para un token aleatorio de 32 caracteres
        });

        // Evento 'updating' para detectar cambios en campos específicos y reiniciar Supervisor
        static::updating(function ($rfidReading) {
            if ($rfidReading->isDirty(['mqtt_topic_rfid', 'epc'])) {
                self::restartSupervisor();
            }
        });

        // Evento 'deleted' para reiniciar supervisor al eliminar el registro
        static::deleted(function ($rfidReading) {
            self::restartSupervisor();
        });
    }

    /**
     * Método para generar un código único usando producción, ID del RFID y fecha.
     */
    public function generateUniqueCode()
    {
        // Obtener el id de la línea de producción
        $lineId = $this->production_line_id;

        // Obtener el id del RFID
        $rfidId = $this->id;

        // Obtener la fecha y hora actual en formato numérico
        $timestamp = Carbon::now()->format('YmdHis'); // Formato: YYYYMMDDHHMMSS

        // Concatenar el id de la línea, el id del RFID y el timestamp
        return $lineId . '_' . $rfidId . '_' . $timestamp;
    }

    /**
     * Método para reiniciar el Supervisor.
     */
    protected static function restartSupervisor()
    {
        try {
            // Usa sudo para ejecutar supervisorctl sin contraseña
            exec('sudo /usr/bin/supervisorctl restart all', $output, $returnVar);

            if ($returnVar === 0) {
                Log::channel('supervisor')->info("Supervisor reiniciado exitosamente.");
            } else {
                Log::channel('supervisor')->error("Error al reiniciar supervisor: " . implode("\n", $output));
            }
        } catch (\Exception $e) {
            Log::channel('supervisor')->error("Excepción al reiniciar supervisor: " . $e->getMessage());
        }
    }
}
