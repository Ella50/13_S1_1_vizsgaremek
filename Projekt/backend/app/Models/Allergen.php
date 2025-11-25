<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allergen extends Model
{
    use HasFactory;
    protected $table = 'allergen';

    protected $fillable = [
        'name',
        'icon',

    ];

    // Kapcsolat User modellel
    public function userHealthRestriction()
    {
        return $this->hasMany(UserHealthRestriction::class, 'allergen_id');
    }
        public function ingredientAllergen()
    {
        return $this->hasMany(IngredientAllergen::class, 'allergen_id');
    }
}