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


    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'price_id');
    }


    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }


    public function scopeActive($query)
    {
        return $query->where('orderStatus', 'Rendelve');
    }


    public function scopeCancelled($query)
    {
        return $query->where('orderStatus', 'Lemondva');
    }


    public function scopePaid($query)
    {
        return $query->where('orderStatus', 'Fizetve');
    }


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
    

    public function isActive(): bool
    {
        return $this->orderStatus === 'Rendelve';
    }

    public function canBeCancelled(): bool
    {
        return $this->isActive() && 
               (!$this->menuItem->order_deadline || now() < $this->menuItem->order_deadline);
    }
}