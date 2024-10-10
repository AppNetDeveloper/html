<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStat extends Model
{
    use HasFactory;

    // Especifica la tabla asociada a este modelo.
    protected $table = 'order_stats';

    // Permite la asignación masiva de estos campos.
    protected $fillable = [
        'production_line_id',
        'order_id',
        'units',
        'units_per_minute_real',
        'units_per_minute_theoretical',
        'seconds_per_unit_real',
        'seconds_per_unit_theoretical',
        'units_made_real',
        'units_made_theoretical',
        'sensor_stops_count',
        'sensor_stops_time',
        'production_stops_count',
        'production_stops_time',
        'units_made',
        'units_pending',
        'units_delayed',
        'slow_time',
        'oee',
    ];

    // Asegúrate de que se gestionen las marcas de tiempo automáticamente.
    public $timestamps = true;
}
