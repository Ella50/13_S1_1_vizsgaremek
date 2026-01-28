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


    public function getMenuWithMeals()
    {
        return [
            'id' => $this->id,
            'day' => $this->day,
            'soup' => $this->soupMeal ? [
                'id' => $this->soupMeal->id,
                'mealName' => $this->soupMeal->mealName,
                'description' => $this->soupMeal->description,
                'category' => $this->soupMeal->mealType
            ] : null,
            'optionA' => $this->optionAMeal ? [
                'id' => $this->optionAMeal->id,
                'mealName' => $this->optionAMeal->mealName,
                'description' => $this->optionAMeal->description,
                'category' => $this->optionAMeal->mealType,
                'price' => $this->optionAMeal->price ?? null
            ] : null,
            'optionB' => $this->optionBMeal ? [
                'id' => $this->optionBMeal->id,
                'mealName' => $this->optionBMeal->mealName,
                'description' => $this->optionBMeal->description,
                'category' => $this->optionBMeal->mealType,
                'price' => $this->optionBMeal->price ?? null
            ] : null,
            'other' => $this->otherMeal ? [
                'id' => $this->otherMeal->id,
                'mealName' => $this->otherMeal->mealName,
                'description' => $this->otherMeal->description,
                'category' => $this->otherMeal->mealType
            ] : null,
        ];
    }

    // MenuItem.php fájlba hozzáadni:

/**
 * Menü adatainak lekérése (visszafelé kompatibilis)
 */
    public function getMenuData()
    {
        $data = [
            'id' => $this->id,
            'day' => $this->day,
            'soup' => null,
            'optionA' => null,
            'optionB' => null,
            'other' => null
        ];
        
        // Leves
        if ($this->relationLoaded('soupMeal') && $this->soupMeal) {
            $data['soup'] = [
                'id' => $this->soupMeal->id,
                'mealName' => $this->soupMeal->mealName,
                'description' => $this->soupMeal->description,
                'category' => $this->soupMeal->mealType,
                'isAvailable' => $this->soupMeal->isAvailable ?? true
            ];
        } else if ($this->soup) {
            // Ha nincs betöltve, csak az ID-t adjuk vissza
            $data['soup'] = ['id' => $this->soup];
        }
        
        // A opció
        if ($this->relationLoaded('optionAMeal') && $this->optionAMeal) {
            $data['optionA'] = [
                'id' => $this->optionAMeal->id,
                'mealName' => $this->optionAMeal->mealName,
                'description' => $this->optionAMeal->description,
                'category' => $this->optionAMeal->mealType,
                'isAvailable' => $this->optionAMeal->isAvailable ?? true
            ];
        } else if ($this->optionA) {
            $data['optionA'] = ['id' => $this->optionA];
        }
        
        // B opció
        if ($this->relationLoaded('optionBMeal') && $this->optionBMeal) {
            $data['optionB'] = [
                'id' => $this->optionBMeal->id,
                'mealName' => $this->optionBMeal->mealName,
                'description' => $this->optionBMeal->description,
                'category' => $this->optionBMeal->mealType,
                'isAvailable' => $this->optionBMeal->isAvailable ?? true
            ];
        } else if ($this->optionB) {
            $data['optionB'] = ['id' => $this->optionB];
        }
        
        // Egyéb
        if ($this->relationLoaded('otherMeal') && $this->otherMeal) {
            $data['other'] = [
                'id' => $this->otherMeal->id,
                'mealName' => $this->otherMeal->mealName,
                'description' => $this->otherMeal->description,
                'category' => $this->otherMeal->mealType,
                'isAvailable' => $this->otherMeal->isAvailable ?? true
            ];
        } else if ($this->other) {
            $data['other'] = ['id' => $this->other];
        }
        
        return $data;
    }
}

