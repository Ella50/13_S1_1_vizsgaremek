<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoiceNumber',
        'billingMonth',
        'issueDate',
        'dueDate',
        'totalAmount',
        'paymentMethod',
        'invoiceStatus',
        'transactionId',
        'paidAt'
    ];

    protected $casts = [
        'billingMonth' => 'date',
        'issueDate' => 'date',
        'dueDate' => 'date',
        'totalAmount' => 'decimal:2',
        'paidAt' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isPaid(): bool
    {
        return $this->invoiceStatus === 'Fizetve';
    }

    public function isOverdue(): bool
    {
        return !$this->isPaid() && $this->dueDate < now();
    }
}