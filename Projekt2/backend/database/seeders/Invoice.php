<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\PaymentMethod;
use App\Enums\InvoiceStatus;


class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice';

    protected $fillable = [
        'user_id',
        'invoiceNumber',
        'billingMonth',
        'issueDate',
        'dueDate',
        'totalAmount',
        'paymentMethod',
        'status',
        'transactionId',
        'paidAt'
    ];

    protected $casts = [
        'issueDate' => 'date',
        'dueDate' => 'date',
        'paidAt' => 'datetime',
        'totalAmount' => 'decimal:2',
        'billingMonth' => 'date',
        'status' => InvoiceStatus::class,
        'paymentMethod' => PaymentMethod::class,
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}