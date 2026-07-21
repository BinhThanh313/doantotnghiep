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

    /**
     * Flash sale item đang chạy cho sản phẩm này (nếu có), dùng để đồng bộ
     * giá flash-sale ở mọi nơi hiển thị sản phẩm (home, shop, chi tiết, giỏ hàng...).
     * Luôn eager-load quan hệ này (with('activeFlashSaleItem')) ở các query
     * hiển thị sản phẩm để tránh N+1.
     */
    public function activeFlashSaleItem()
    {
        return $this->hasOne(FlashSaleItem::class, 'product_id')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('qty_limit')
                  ->orWhereColumn('qty_sold', '<', 'qty_limit');
            })
            ->whereHas('flashSale', fn ($q) => $q->running())
            ->latest('id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Sản phẩm có đang được Flash Sale hay không.
     */
    public function getIsFlashSaleAttribute(): bool
    {
        return $this->activeFlashSaleItem !== null;
    }

    /**
     * Giá Flash Sale (null nếu không có Flash Sale đang chạy).
     */
    public function getFlashSalePriceAttribute(): ?float
    {
        return $this->activeFlashSaleItem->sale_price ?? null;
    }

    /**
     * Giá thực tế cần dùng để hiển thị / tính vào giỏ hàng: ưu tiên giá
     * Flash Sale nếu đang chạy, ngược lại dùng giá bán thông thường.
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->activeFlashSaleItem->sale_price ?? $this->price;
    }

    /**
     * % giảm giá so với giá gốc khi đang Flash Sale.
     */
    public function getFlashSaleDiscountPercentAttribute(): int
    {
        if (!$this->is_flash_sale || $this->price <= 0) {
            return 0;
        }
        return (int) round((1 - $this->flash_sale_price / $this->price) * 100);
    }

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
    public function decreaseStock(int $quantity, int $orderId, ?int $variantId = null, ?ProductVariant $variant = null): void
    {
        if ($variantId) {
            // Nếu caller đã có sẵn variant (đã lockForUpdate trước đó trong transaction),
            // dùng lại thay vì SELECT lại lần nữa.
            $variant = $variant ?? $this->variants()->find($variantId);
            if ($variant) {
                $variant->decrement('stock', $quantity);
            }
        } else {
            $this->decrement('stock', $quantity);
        }

        InventoryLog::logPurchase($this, $quantity, $orderId, $variantId);
    }
}