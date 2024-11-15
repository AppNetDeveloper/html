<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BluetoothList extends Model
{
    use HasFactory;

    protected $table = 'bluetooth_list';

    protected $fillable = [
        'name',
        'value',
        'production_line_id',
        'bluetooth_detail_id',
        'bluetooth_reading_id',
        'bluetooth_ant_name',
        'model_product',
        'orderId',
        'count_total',
        'unic_code_order',
        'count_total_1',
        'count_shift_1',
        'count_order_1',
        'time_11',
        'mac',
        'rssi',
        'change', // Incluimos el campo `change` en el fillable
    ];

    /**
     * Relación con la línea de producción.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con el modelo BluetoothDetail.
     */
    public function bluetoothDetail()
    {
        return $this->belongsTo(BluetoothDetail::class);
    }

    /**
     * Relación con el modelo BluetoothReading.
     */
    public function bluetoothReading()
    {
        return $this->belongsTo(BluetoothReading::class);
    }
}
