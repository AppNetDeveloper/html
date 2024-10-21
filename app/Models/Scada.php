<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Scada extends Model
{
    use HasFactory;

    protected $table = 'scada';

    protected $fillable = [
        'production_line_id', 
        'name', 
        'token'
    ];

    // Generar token automáticamente al crear el modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->token = Str::uuid();
        });
    }

    // Relación con la tabla production_lines
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
