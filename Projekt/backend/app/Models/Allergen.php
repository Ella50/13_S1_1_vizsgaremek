<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allergen extends Model
{
    use HasFactory;

    protected $fillable = [
        'allergenName',
        'icon',
    ];

    protected $appends = ['icon_url']; 


    public function getIconUrlAttribute()
    {
        if (!$this->icon) {
            return null;
        }
        
  
        if (filter_var($this->icon, FILTER_VALIDATE_URL)) {
            return $this->icon;
        }
        

        return asset('storage/' . $this->icon);
    }

    public function ingredient()
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_allergens');
    }
}