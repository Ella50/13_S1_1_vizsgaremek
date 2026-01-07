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

    /**
     * Kapcsolat az összetevőkkel
     * FONTOS: A 'meal_ingredients' a kapcsolótábla neve
     */
    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredient::class,      // Kapcsolódó modell
            'meal_ingredients',     // Kapcsolótábla neve
            'meal_id',              // Foreign key a meal_ingredients táblában
            'ingredient_id',        // Related key a meal_ingredients táblában
            'id',                   // Local key a meals táblában
            'id'                    // Local key az ingredients táblában
        )->withPivot('amount', 'unit');
    }
}