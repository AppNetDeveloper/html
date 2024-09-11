<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class Modbus extends Model
{
    protected $fillable = [
        'production_line_id',
        'json_api',
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
        'calibration_type',
        'rec_box', // conteo de cajas por order_notice
        'rec_box_shift',// conteo de cajas por turno
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
