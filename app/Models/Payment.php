<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'amount', 'currency', 'payment_method',
        'transaction_id', 'status', 'gateway_response', 'paid_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
