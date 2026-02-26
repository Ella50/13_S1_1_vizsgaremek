<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Price extends Model
{
    use HasFactory;

    /**
     * A tömeges hozzárendeléshez engedélyezett mezők
     */
    protected $fillable = [
        'userType',
        'priceCategory',
        'amount',
        'validFrom',
        'validTo'
    ];

    /**
     * Típuskonverziók
     */
    protected $casts = [
        'validFrom' => 'date',
        'validTo' => 'date',
        'amount' => 'decimal:2'
    ];
    
    public $timestamps = false;

    /**
     * Kapcsolat a rendelésekkel
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope aktuális árakhoz
     */
    public function scopeActive($query)
    {
        $now = now()->format('Y-m-d');
        return $query->where('validFrom', '<=', $now)
                     ->where(function($q) use ($now) {
                         $q->whereNull('validTo')
                           ->orWhere('validTo', '>=', $now);
                     });
    }


    public function scopeForUserType($query, $userType)
    {
        return $query->where('userType', $userType);
    }


    public function scopeForCategory($query, $category)
    {
        return $query->where('priceCategory', $category);
    }

    /**
     * Aktív-e az ár
     */
    public function isActive(): bool
    {
        $now = now()->format('Y-m-d');
        return $this->validFrom <= $now && 
               ($this->validTo === null || $this->validTo >= $now);
    }

    /**
     * Formázott ár
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' Ft';
    }

    /**
     * Felhasználó típus megjelenítési neve
     */
    public function getUserTypeDisplayAttribute(): string
    {
        return match($this->userType) {
            'Tanuló' => 'Tanuló',
            'Tanár' => 'Tanár',
            'Dolgozó' => 'Dolgozó',
            'Külsős' => 'Külsős',
            default => $this->userType
        };
    }

    /**
     * Kategória megjelenítési neve
     */
    public function getCategoryDisplayAttribute(): string
    {
        return match($this->priceCategory) {
            'Normál' => 'Normál ár',
            'Kedvezményes' => 'Kedvezményes ár',
            default => $this->priceCategory
        };
    }
}