<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ShiftControl extends Model
{
    use HasFactory;

    // Definir la tabla asociada si el nombre no sigue el plural estándar
    protected $table = 'shift_control';

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'name',
        'production_line_id',
        'mqtt_topic',
        'shift_type',
        'event',
    ];

    // Laravel manejará automáticamente created_at y updated_at
    public $timestamps = true;

    // Relación con ProductionLine
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    // Relación con ControlWeight
    public function controlWeights()
    {
        return $this->hasMany(ControlWeight::class);
    }

    // Relación con ControlHeight
    public function controlHeights()
    {
        return $this->hasMany(ControlHeight::class);
    }

    // Eventos de Eloquent
    protected static function boot()
    {
        parent::boot();

        // Evento que se activa al actualizar
        static::updating(function ($shiftControl) {
            if ($shiftControl->isDirty(['mqtt_topic'])) {
                self::restartSupervisor();
            }
        });

        // Evento que se activa al crear
        static::created(function ($shiftControl) {
            self::restartSupervisor();
        });

        // Evento que se activa al eliminar
        static::deleted(function ($shiftControl) {
            self::restartSupervisor();
        });
    }

    // Método para reiniciar Supervisor
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
