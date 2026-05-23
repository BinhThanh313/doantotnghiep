<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'name', 'discount_type', 'discount_value',
        'min_amount', 'max_discount', 'max_uses', 'max_uses_per_user',
        'used_count', 'start_date', 'end_date',
        'applicable_categories', 'applicable_products', 'is_active',
    ];

    protected $casts = [
        'applicable_categories' => 'array',
        'applicable_products'   => 'array',
        'start_date'            => 'datetime',
        'end_date'              => 'datetime',
        'is_active'             => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_vouchers')
                    ->withPivot('discount_amount')
                    ->withTimestamps();
    }

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Kiểm tra voucher còn hợp lệ không
     */
    public function isValid(): bool
    {
        if (!$this->is_active) return false;

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) return false;
        if ($this->end_date   && $now->gt($this->end_date))   return false;

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;

        return true;
    }

    /**
     * Tính số tiền giảm cho đơn hàng
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if ($orderAmount < $this->min_amount) return 0;

        if ($this->discount_type === 'fixed') {
            return min($this->discount_value, $orderAmount);
        }

        // percent
        $discount = $orderAmount * ($this->discount_value / 100);

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return $discount;
    }

    /**
     * Kiểm tra user đã dùng voucher này chưa
     */
    public function hasBeenUsedByUser(int $userId): bool
    {
        $count = $this->usages()->where('user_id', $userId)->count();
        return $count >= $this->max_uses_per_user;
    }
}
