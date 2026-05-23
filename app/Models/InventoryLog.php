<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    protected $fillable = [
        'product_id', 'variant_id', 'quantity_change',
        'stock_before', 'stock_after', 'reason',
        'reference_id', 'reference_type', 'notes', 'created_by',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    /**
     * Tạo log trừ kho khi có đơn hàng
     */
    public static function logPurchase(Product $product, int $quantity, int $orderId, ?int $variantId = null): void
    {
        $stockBefore = $variantId
            ? ProductVariant::find($variantId)?->stock ?? $product->stock
            : $product->stock;

        self::create([
            'product_id'      => $product->id,
            'variant_id'      => $variantId,
            'quantity_change' => -$quantity,
            'stock_before'    => $stockBefore,
            'stock_after'     => $stockBefore - $quantity,
            'reason'          => 'purchase',
            'reference_id'    => $orderId,
            'reference_type'  => 'order',
        ]);
    }
}
