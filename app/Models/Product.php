<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
    'category_id', 'name', 'slug', 'description',
    'meta_description', 'meta_keywords', 'og_image', 'tags',
    'price', 'original_price', 'image', 'stock',
    'is_new', 'is_active', 'is_bestseller', 'view_count',
    ];

    protected $casts = [
        'is_new'        => 'boolean',
        'is_active'     => 'boolean',
        'is_bestseller' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true)->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    // ==================== ACCESSORS ====================

    /**
     * Rating trung bình của sản phẩm
     */
    public function getAvgRatingAttribute(): float
    {
        return round($this->reviews()->where('is_visible', true)->avg('rating') ?? 0, 1);
    }

    /**
     * Số lượng đánh giá
     */
    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->where('is_visible', true)->count();
    }

    /**
     * Gom thông số kỹ thuật theo nhóm để hiển thị, dạng:
     * ['Màn hình' => [['label' => ..., 'value' => ..., 'unit' => ...], ...], ...]
     */
    public function getSpecificationsGroupedAttribute(): \Illuminate\Support\Collection
    {
        return $this->specifications
            ->groupBy('group_name')
            ->map(fn ($items) => $items->map(fn ($s) => [
                'label' => $s->label,
                'value' => $s->value,
                'unit'  => $s->unit,
            ])->values());
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query, int $threshold = 5)
    {
        return $query->where('stock', '<=', $threshold)->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    // ==================== HELPERS ====================

    /**
     * Auto-generate slug khi set name
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'like', $slug . '%')->count();
        return $count > 0 ? $slug . '-' . ($count + 1) : $slug;
    }

    /**
     * Trừ kho và ghi log
     */
    public function decreaseStock(int $quantity, int $orderId, ?int $variantId = null): void
    {
        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) {
                $variant->decrement('stock', $quantity);
            }
        } else {
            $this->decrement('stock', $quantity);
        }

        InventoryLog::logPurchase($this, $quantity, $orderId, $variantId);
    }
}