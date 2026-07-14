<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCombo extends Model
{
    protected $fillable = [
        'product_id', 'combo_product_id', 'discount_percent', 'is_active', 'similarity_score',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function comboProduct()
    {
        return $this->belongsTo(Product::class, 'combo_product_id');
    }

    /**
     * Lấy toàn bộ combo đang hoạt động có liên quan tới 1 sản phẩm
     * (dù sản phẩm đó nằm ở vế product_id hay combo_product_id),
     * kèm sản phẩm "đi kèm" tương ứng — dùng cho trang chi tiết sản phẩm.
     */
    public static function activeForProduct(int $productId)
    {
        return static::with(['product', 'comboProduct'])
            ->where('is_active', true)
            ->where(function ($q) use ($productId) {
                $q->where('product_id', $productId)
                  ->orWhere('combo_product_id', $productId);
            })
            ->get()
            ->map(function ($combo) use ($productId) {
                $partner = $combo->product_id === $productId ? $combo->comboProduct : $combo->product;
                return [
                    'id'               => $combo->id,
                    'partner'          => $partner,
                    'discount_percent' => $combo->discount_percent,
                ];
            })
            ->filter(fn ($row) => $row['partner'] !== null)
            ->values();
    }
}
