<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $table = 'meal';

    protected $fillable = [
        'mealName',
        'mealType',
        'description',
        'picture'
    ];

    /* Kapcsolat User modellel
    public function user()
    {
        return $this->hasOne(User::class, 'rfidCard_id');
    }*/
}