<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorCount extends Model
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
        'name',
        'sensor_id', // ID del sensor relacionado
        'value',
        'production_line_id',
        'model_product', 
        'orderId',
        'count_total_0',
        'count_total_1',
        'count_shift_0',
        'count_shift_1',
        'count_order_0',
        'count_order_1',
        'time_00',
        'time_01',
        'time_11',
        'time_10',
        'unic_code_order',      // codigo de orden unico, uso interno nada mas
    ];

    /**
     * Relación con la tabla ProductionLine.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con la tabla ControlWeight.
     */
    public function controlWeights()
    {
        return $this->hasMany(ControlWeight::class);
    }

    /**
     * Relación con la tabla ControlHeight.
     */
    public function controlHeights()
    {
        return $this->hasMany(ControlHeight::class);
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
}
