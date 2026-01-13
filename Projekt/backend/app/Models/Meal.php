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


    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredient::class,    
            'meal_ingredients',    //tábla név
            'meal_id',             
            'ingredient_id',        
            'id',                   //meals tábla
            'id'                    //ingredients tábla
        )->withPivot('amount', 'unit');
    }
}