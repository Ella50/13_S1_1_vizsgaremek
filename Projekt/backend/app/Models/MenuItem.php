<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $table = 'menuItems';

    protected $fillable = [
        'day',
        'soup',
        'optionA',
        'optionB'
    ];

    public function soupMeal()
    {
        return $this->belongsTo(Meals::class, 'soup');
    }

    public function optionAMeal()
    {
        return $this->belongsTo(Meals::class, 'optionA');
    }

    public function optionBMeal()
    {
        return $this->belongsTo(Meals::class, 'optionB');
    }
}

