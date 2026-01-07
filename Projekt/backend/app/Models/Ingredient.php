<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;
    protected $table = 'ingredients';

    protected $fillable = [
        'name',
        'ingredientType',
        'energy',
        'protein',
        'carbohydrate',
        'fat',
        'sodium',
        'sugar',
        'fiber',
        'isAvailable'
    ];

    public function meals()
        {
            return $this->belongsToMany(Meal::class, 'meal_ingredients')
                        ->using(MealIngredientPivot::class)
                        ->withPivot('amount', 'unit')
                        ->withTimestamps();
        }
}