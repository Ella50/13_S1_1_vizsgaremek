<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidCard extends Model
{
    use HasFactory;

    protected $table = 'rfidCard';

    protected $fillable = [
        'cardNumber',
        'lastUsedAt',
        'isActive'
    ];

    // Kapcsolat User modellel
    public function user()
    {
        return $this->hasOne(User::class, 'rfidCard_id');
    }
}