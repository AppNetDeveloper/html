<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaList extends Model
{
    use HasFactory;

    protected $table = 'scada_list';

    protected $fillable = [
        'scada_id', 
        'modbus_id', 
        'fillinglevels', 
        'material_type_id'
    ];

    // Relación con la tabla Scada
    public function scada()
    {
        return $this->belongsTo(Scada::class);
    }

    // Relación con la tabla Modbus
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }

    // Relación con la tabla ScadaMaterialType
    public function materialType()
    {
        return $this->belongsTo(ScadaMaterialType::class, 'material_type_id');
    }
}
