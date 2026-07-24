<?php
// app/Models/FlashSale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FlashSale extends Model
{
    protected $fillable = [
        'name', 'description', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function activeItems()
    {
        return $this->hasMany(FlashSaleItem::class)
            ->where('is_active', true)
            ->whereRaw('(qty_limit IS NULL OR qty_sold < qty_limit)');
    }

    public function isRunning(): bool
    {
        $now = Carbon::now();
        return $this->is_active
            && $now->gte($this->starts_at)
            && $now->lte($this->ends_at);
    }

    public function getStatusAttribute(): string
    {
        $now = Carbon::now();
        if (!$this->is_active) return 'disabled';
        if ($now->lt($this->starts_at)) return 'upcoming';
        if ($now->gt($this->ends_at))   return 'ended';
        return 'running';
    }

    public function getSecondsRemainingAttribute(): int
    {
        if ($this->status !== 'running') return 0;
        return (int) Carbon::now()->diffInSeconds($this->ends_at, false);
    }

    /** Scope: đang chạy */
    public function scopeRunning($query)
    {
        return $query->where('is_active', true)
                     ->where('starts_at', '<=', now())
                     ->where('ends_at',   '>=', now());
    }
}