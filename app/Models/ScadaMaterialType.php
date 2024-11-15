<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaMaterialType extends Model
{
    use HasFactory;

    protected $table = 'scada_material_type';

    protected $fillable = [
        'scada_id', 
        'name', 
        'density'
    ];

    // Relación con la tabla Scada
    public function scada()
    {
        return $this->belongsTo(Scada::class);
    }
}
