<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidCard extends Model
{
    use HasFactory;

    protected $table = 'rfidCards';

    protected $fillable = [
        'cardNumber',
        'lastUsedAt',
        'isActive'
    ];


    public function user()
    {
        return $this->hasOne(User::class, 'rfidCard_id');
    }
}