<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppCredential extends Model
{
    use HasFactory;

    // Especifica la tabla asociada
    protected $table = 'whatsapp_credentials';

    // Define los campos que se pueden rellenar
    protected $fillable = [
        'creds',
        'keys',
    ];

    // Relación de ejemplo con el modelo User (si es necesario)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Puedes agregar más relaciones según sea necesario
}
