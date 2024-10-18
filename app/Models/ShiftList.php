<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
