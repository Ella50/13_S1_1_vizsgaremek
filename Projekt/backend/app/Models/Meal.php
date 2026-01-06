<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $table = 'meals';

    protected $fillable = [
        'mealName',
        'mealType',
        'description',
        'picture'
    ];

    /* Kapcsolat modellel
    public function user()
    {
        return $this->hasOne(User::class, 'rfidCard_id');
    }*/
}