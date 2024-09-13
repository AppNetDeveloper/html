<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'name',
        'token',
    ];

    /**
     * Los atributos que deberían estar ocultos para los arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        //'token', // Ocultamos el token por seguridad
    ];

    // Define la relación con la tabla customers
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function barcodes()
    {
        return $this->hasMany(Barcode::class);
    }
    // Relación inversa con Sensor
    public function sensors()
    {
        return $this->hasMany(Sensor::class, 'production_line_id');
    }
}
