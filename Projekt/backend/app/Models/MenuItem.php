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
        'optionB',
        'other'
    ];

        protected $casts = [
        'day' => 'date' 
    ];

    public function soupMeal() { return $this->belongsTo(Meal::class, 'soup'); }
    public function optionAMeal() { return $this->belongsTo(Meal::class, 'optionA'); }
    public function optionBMeal() { return $this->belongsTo(Meal::class, 'optionB'); }
    public function otherMeal() { return $this->belongsTo(Meal::class, 'other'); }
    public function orders(){ return $this->hasMany(Order::class, 'menuItems_id'); }
}

