<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealIngredient extends Model
{
    use HasFactory;

    protected $table = 'meal_ingredient';

    protected $fillable = [
        'meal_id',
        'ingredient_id',
        'amount',
        'unit'

    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class, 'meal_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient _id');
    }

}