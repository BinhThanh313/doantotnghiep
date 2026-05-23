<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'customer_name', 'customer_email',
        'customer_phone', 'address', 'province',
        'total_amount', 'shipping_fee', 'discount_amount',
        'status', 'payment_status', 'payment_method',
        'notes', 'tracking_number',
    ];

    // ==================== RELATIONSHIPS ====================

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'order_vouchers')
                    ->withPivot('discount_amount')
                    ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // ==================== ACCESSORS ====================

    public function getGrandTotalAttribute(): float
    {
        return $this->total_amount + $this->shipping_fee - $this->discount_amount;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'       => 'Chờ xử lý',
            'processing'    => 'Đang chuẩn bị',
            'ready_to_ship' => 'Sẵn sàng giao',
            'shipped'       => 'Đang vận chuyển',
            'delivered'     => 'Đã giao hàng',
            'completed'     => 'Hoàn thành',
            'cancelled'     => 'Đã hủy',
            default         => $this->status,
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid'   => 'Chưa thanh toán',
            'paid'     => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
            default    => $this->payment_status,
        };
    }

    // ==================== HELPERS ====================

    /**
     * Tạo tracking number duy nhất
     */
    public static function generateTrackingNumber(): string
    {
        return 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Kiểm tra order có thể hủy không
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }
}