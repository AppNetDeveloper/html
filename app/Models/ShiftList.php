<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ShiftList extends Model
{
    use HasFactory;

    /**
     * Habilitar el manejo automático de timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Los atributos que son asignables.
     *
     * @var array
     */
    protected $fillable = [
        'production_line_id',
        'start',
        'end',
    ];

    // Definir la relación con la producción
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();

        // Evento 'creating' para reiniciar Supervisor al crear un nuevo registro
        static::creating(function ($shiftList) {
            self::restartSupervisor();
        });

        // Evento 'updating' para reiniciar Supervisor al actualizar un registro
        static::updating(function ($shiftList) {
            if ($shiftList->isDirty(['production_line_id', 'start', 'end'])) {
                self::restartSupervisor();
            }
        });

        // Evento 'deleted' para reiniciar Supervisor al eliminar un registro
        static::deleted(function ($shiftList) {
            self::restartSupervisor();
        });
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
