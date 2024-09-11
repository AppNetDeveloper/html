<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlWeight extends Model
{
    protected $fillable = [
        'modbus_id',
        'last_control_weight',
        'last_dimension',
        'last_box_number',
        'last_box_shift',
        'last_barcoder',
        'last_final_barcoder',
    ];

    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }
}
