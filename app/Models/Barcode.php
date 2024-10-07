<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'name',
        'token',
        'mqtt_topic_barcodes',
        'machine_id',
        'ope_id',
        'order_notice',
        'last_barcode',
        'ip_zerotier',
        'user_ssh',
        'port_ssh',
        'ip_barcoder',
        'user_ssh_password',
        'port_barcoder'.
        'conexion_type',
        'iniciar_model',
        'sended',
    ];

    protected $hidden = [
        //'token',
    ];

    protected static function boot()
    {
        parent::boot();

        // Evento 'updating' para detectar cambios
        static::updating(function ($sensor) {
            if ($sensor->isDirty([
                'ip_barcoder', 
                'port_barcoder',
            ])) {
                self::restartSupervisor();
            }
        });
        static::created(function ($sensor) {
            
            self::restartSupervisor();
       });
        static::deleted(function ($sensor) {
            self::restartSupervisor();
        });
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
