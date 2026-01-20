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

    protected $appends = ['icon_url']; // Hozzáadjuk a virtuális attribútumot


    public function getIconUrlAttribute()
    {
        if (!$this->icon) {
            return null;
        }
        
        // Ha teljes URL van mentve, azt adjuk vissza
        if (filter_var($this->icon, FILTER_VALIDATE_URL)) {
            return $this->icon;
        }
        
        // Egyébként asset() függvénnyel generáljuk a teljes URL-t
        return asset('storage/' . $this->icon);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_allergens');
    }
}