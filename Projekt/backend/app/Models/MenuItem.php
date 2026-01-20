<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meal;

class MenuItem extends Model
{
    protected $table = 'menuItems';

    protected $fillable = [
        'day',
        'soup',
        'optionA',
        'optionB'
    ];

    public function soupMeal() { return $this->belongsTo(Meal::class, 'soup'); }
    public function optionAMeal() { return $this->belongsTo(Meal::class, 'optionA'); }
    public function optionBMeal() { return $this->belongsTo(Meal::class, 'optionB'); }
    public function otherMeal() { return $this->belongsTo(Meal::class, 'other'); }

}

