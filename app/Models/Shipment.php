<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'carrier_id', 'tracking_number', 'shipping_fee',
        'estimated_delivery', 'actual_delivery', 'status', 'notes',
    ];

    protected $casts = [
        'estimated_delivery' => 'datetime',
        'actual_delivery'    => 'datetime',
    ];

    public function order()   { return $this->belongsTo(Order::class); }
    public function carrier() { return $this->belongsTo(ShippingCarrier::class, 'carrier_id'); }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'Chờ lấy hàng',
            'in_transit' => 'Đang vận chuyển',
            'delivered'  => 'Đã giao hàng',
            'failed'     => 'Giao thất bại',
            'returned'   => 'Hoàn hàng',
            default      => (string) $this->status,
        };
    }
}
