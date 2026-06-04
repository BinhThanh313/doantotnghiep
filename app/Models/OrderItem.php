<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'quantity',
        'price',
        'original_price',
        'discount_percent',
    ];

    protected $casts = [
        'price'            => 'float',
        'original_price'   => 'float',
        'discount_percent' => 'float',
        'quantity'         => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault([
            'name'  => $this->product_name,
            'price' => $this->price,
        ]);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class)->withDefault();
    }

    // ==================== ACCESSORS ====================

    /**
     * Tổng tiền của item này (price * quantity)
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Số tiền giảm giá của item này
     */
    public function getDiscountAmountAttribute(): float
    {
        if (!$this->original_price || !$this->discount_percent) return 0;
        return ($this->original_price - $this->price) * $this->quantity;
    }

    // ==================== HELPERS ====================

    /**
     * Kiểm tra stock trước khi thêm vào order
     */
    public static function validateStock(int $productId, int $quantity, ?int $variantId = null): bool
    {
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            return $variant && $variant->stock >= $quantity;
        }

        $product = Product::find($productId);
        return $product && $product->stock >= $quantity;
    }

    /**
     * Trừ stock sau khi đặt hàng thành công
     */
    public function deductStock(): void
    {
        if ($this->product_variant_id) {
            ProductVariant::where('id', $this->product_variant_id)
                          ->decrement('stock', $this->quantity);
        } else {
            Product::where('id', $this->product_id)
                   ->decrement('stock', $this->quantity);
        }
    }

    /**
     * Hoàn trả stock khi hủy đơn
     */
    public function restoreStock(): void
    {
        if ($this->product_variant_id) {
            ProductVariant::where('id', $this->product_variant_id)
                          ->increment('stock', $this->quantity);
        } else {
            Product::where('id', $this->product_id)
                   ->increment('stock', $this->quantity);
        }
    }
}