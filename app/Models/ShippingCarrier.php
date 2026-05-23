<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingCarrier extends Model
{
    protected $fillable = [
        'name', 'code', 'api_key', 'api_url',
        'is_active', 'base_fee', 'per_km_fee',
    ];

    public function zones()
    {
        return $this->hasMany(ShippingZone::class, 'carrier_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'carrier_id');
    }

    /**
     * Tính phí ship theo tỉnh thành
     */
    public function getFeeForProvince(string $province): float
    {
        $zone = $this->zones()->where('province', $province)->first();
        if ($zone) return $zone->fee;

        // Fallback: base_fee nếu không có zone
        return $this->base_fee;
    }
}
