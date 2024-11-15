<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidList extends Model
{
    use HasFactory;

    protected $table = 'rfid_list';

    protected $fillable = [
        'name',
        'value',
        'production_line_id',
        'rfid_detail_id',
        'rfid_reading_id',
        'rfid_ant_name',
        'model_product',
        'orderId',
        'count_total',
        'unic_code_order',
        'count_total_1',
        'count_shift_1',
        'count_order_1',
        'time_11',
        'epc',
        'tid',
        'rssi',
        'serialno',
        'ant',
    ];

    /**
     * Relación con la línea de producción
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con el modelo RfidDetail.
     */
    public function rfidDetail()
    {
        return $this->belongsTo(RfidDetail::class);
    }

    /**
     * Relación con el modelo RfidReading.
     */
    public function rfidReading()
    {
        return $this->belongsTo(RfidReading::class);
    }
}
