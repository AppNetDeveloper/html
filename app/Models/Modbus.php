<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modbus extends Model
{
    protected $fillable = [
        'production_line_id',
        'json_api',
        'mqtt_server',
        'mqtt_port',
        'mqtt_topic_modbus', // Corregido el nombre del campo
        'mqtt_topic_gross',
        'mqtt_topic_control',
        'mqtt_topic_boxcontrol',
        'token',
        'dimension_id',
        'dimension',
        'max_kg',
        'rep_number',
        'min_kg',
        'last_kg',
        'last_rep',
        'tara',
        'tara_calibrate',
        'rec_box', // Esta es la columna que vamos a actualizar
        'model_name',
        'name',
        'last_value', // último valor de la modbus
        'variacion_number',
        'dimension_default', // dimensión por defecto
        'dimension_max', // dimensión máxima
        'dimension_variacion', // variación de dimensión
        'offset_meter',
        'printer_id',
    ];

    public $timestamps = true; // Habilitar el manejo automático de timestamps

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function controlWeights()
    {
        return $this->hasMany(ControlWeight::class);
    }
     // Definir la relación con ControlHeight
     
     public function controlHeights()
     {
         return $this->hasMany(ControlHeight::class);
     }

     protected static function boot()
     {
         parent::boot();
 
         static::updating(function ($modbus) {
             if ($modbus->isDirty([
                'mqtt_server', 
                'mqtt_port', 
                'mqtt_topic_modbus',
                'mqtt_topic_gross',
                'mqtt_topic_control',
                'mqtt_topic_boxcontrol',])) {
                    self::restartSupervisor();
                }
         });
 
         static::created(function ($modbus) {
             self::restartSupervisor();
         });
 
         static::deleted(function ($modbus) {
             self::restartSupervisor();
         });
     }
 
     protected static function restartSupervisor()
     {
         try {
             exec('supervisorctl restart modbus:read', $output, $returnVar);
 
             if ($returnVar === 0) {
                 Log::info("Supervisor reiniciado exitosamente para modbus:read.");
             } else {
                 Log::error("Error al reiniciar supervisor: " . implode("\n", $output));
             }
         } catch (\Exception $e) {
             Log::error("Excepción al reiniciar supervisor: " . $e->getMessage());
         }
     }
 }
