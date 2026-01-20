<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MealIngredientPivot extends Pivot
{
    protected $table = 'meal_ingredients';

    public $timestamps = false;
    
    protected $casts = [
        'amount' => 'decimal:2',
        'unit'
    ];
    
    public function getAmountUnit()
    {
        return $this->amount . ' ' . $this->unit;
    }
}