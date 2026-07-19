<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'name', 'attributes',
        'price', 'original_price', 'stock', 'image', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class, 'variant_id');
    }

    /**
     * Lấy giá hiển thị (giá variant hoặc giá product nếu null)
     */
    public function getDisplayPriceAttribute(): float
    {
        return $this->price ?? $this->product->price;
    }
}