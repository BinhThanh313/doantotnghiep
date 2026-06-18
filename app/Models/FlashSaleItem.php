<?php
// app/Models/FlashSaleItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleItem extends Model
{
    protected $fillable = [
        'flash_sale_id', 'product_id',
        'sale_price', 'qty_limit', 'qty_sold', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getQtyRemainingAttribute(): ?int
    {
        if ($this->qty_limit === null) return null;
        return max(0, $this->qty_limit - $this->qty_sold);
    }

    public function isSoldOut(): bool
    {
        if ($this->qty_limit === null) return false;
        return $this->qty_sold >= $this->qty_limit;
    }

    /** % đã bán so với giới hạn */
    public function getSoldPercentAttribute(): int
    {
        if (!$this->qty_limit) return 0;
        return (int) min(100, round(($this->qty_sold / $this->qty_limit) * 100));
    }
}