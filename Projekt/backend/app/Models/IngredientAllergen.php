<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientAllergen extends Model
{
    use HasFactory;
    protected $table = 'ingredient_allergen';

    protected $fillable = [
        'allergen_id',
        'ingredient_id',
        
    ];

    // Kapcsolat County modellel
    public function allergen()
    {
        return $this->belongsTo(IngredientAllergen::class, 'allergen_id');
    }

    // Kapcsolat User modellel
    public function ingredient()
    {
        return $this->belongsTo(User::class, 'ingredient_id');
    }
}