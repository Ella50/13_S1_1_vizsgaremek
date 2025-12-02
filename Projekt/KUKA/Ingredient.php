<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;
    protected $table = 'ingredient';

    protected $fillable = [
        'name',
        'type',
        'energy',
        'protein',
        'carbohydrate',
        'fat',
        'sodium',
        'sugar',
        'fiber',
        'isAvailable'
    ];


    public function ingredientAllergen()
    {
        return $this->belongsTo(IngredientAllergen::class, 'ingredient_id');
    }

    public function mealIngredient()
    {
        return $this->belongsTo(IngredientAllergen::class, 'ingredient_id');
    }

}