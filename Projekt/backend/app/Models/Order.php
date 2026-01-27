<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'menuItems_id',
        'orderDate',
        'selectedOption',
        'orderStatus',
        'invoice_id',
        'price_id',
        'cancelledAt'
    ];
    
    
    protected $casts = [
        'orderDate' => 'date',
        'cancelledAt' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menuItems_id');
    }

    /**
     * Kapcsolat az árazással
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }

    /**
     * Kapcsolat a számlával
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Scope aktív rendelésekhez
     */
    public function scopeActive($query)
    {
        return $query->where('orderStatus', 'Rendelve');
    }

    /**
     * Scope lemondott rendelésekhez
     */
    public function scopeCancelled($query)
    {
        return $query->where('orderStatus', 'Lemondva');
    }

    /**
     * Scope kifizetett rendelésekhez
     */
    public function scopePaid($query)
    {
        return $query->where('orderStatus', 'Fizetve');
    }

    /**
     * Opció megjelenítési neve
     */
    public function getOptionDisplayAttribute(): string
    {
        return match($this->selected_option) {
            'A' => 'A opció',
            'B' => 'B opció',
            'soup' => 'Leves',
            'other' => 'Egyéb',
            default => $this->selected_option
        };
    }
    

    /**
     * Aktív-e a rendelés
     */
    public function isActive(): bool
    {
        return $this->orderStatus === 'Rendelve';
    }

    /**
     * Lemondható-e még a rendelés
     */
    public function canBeCancelled(): bool
    {
        return $this->isActive() && 
               (!$this->menuItem->order_deadline || now() < $this->menuItem->order_deadline);
    }
}