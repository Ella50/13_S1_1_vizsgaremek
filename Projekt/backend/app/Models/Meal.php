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

    public function allergens()
    {
        return $this->hasManyThrough(
            Allergen::class,
            Ingredient::class,
            'id', // Foreign key on ingredients table
            'id', // Foreign key on allergens table
            'id', // Local key on meals table
            'id'  // Local key on ingredients table
        )->distinct();
    }

    /**
     * Alternatív megoldás: allergének összetevőkön keresztül
     */
    public function getAllAllergensAttribute()
    {
        return $this->ingredients->flatMap(function ($ingredient) {
            return $ingredient->allergens;
        })->unique('id');
    }

    /**
     * Ellenőrzés, hogy tartalmaz-e bizonyos allergént
     */
    public function containsAllergen($allergenId)
    {
        return $this->ingredients()
            ->whereHas('allergens', function($query) use ($allergenId) {
                $query->where('allergens.id', $allergenId);
            })
            ->exists();
    }

    /**
     * Összes allergén ID lekérdezése
     */
    public function getAllergenIdsAttribute()
    {
        return $this->allAllergens->pluck('id')->toArray();
    }

    /**
     * Összetevők allergének nélkül
     */
    public function ingredientsWithoutAllergens()
    {
        return $this->ingredients()->whereDoesntHave('allergens');
    }

    /**
     * Összetevők bizonyos allergénnel
     */
    public function ingredientsWithAllergen($allergenId)
    {
        return $this->ingredients()->whereHas('allergens', function($query) use ($allergenId) {
            $query->where('allergens.id', $allergenId);
        });
    }
}