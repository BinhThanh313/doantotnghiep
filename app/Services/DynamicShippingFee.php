<?php
/**
 * PATCH cho CheckoutController.php
 *
 * Thay thế / bổ sung 2 method:
 *  1. calculateShipping()  — trả về phí ship động theo carrier + province
 *  2. store()              — dùng phí ship động thay vì hardcode
 *
 * Các phần khác của CheckoutController giữ nguyên.
 */

// ─────────────────────────────────────────────────────────────
// 1. calculateShipping  (đã có, giữ nguyên logic — không thay đổi)
// ─────────────────────────────────────────────────────────────
/**
 * POST /checkout/shipping-fee  (web) & POST /api/checkout/shipping-fee (api)
 * Body: { province, carrier_id }
 * Response: { fee, estimated_days, message, carrier_name }
 */
// public function calculateShipping(Request $request)  ← đã có trong file gốc, không cần thêm

// ─────────────────────────────────────────────────────────────
// 2. store() — phần tính shipping fee (thay đoạn hardcode)
// ─────────────────────────────────────────────────────────────
/*
 REPLACE đoạn này trong store():

    $shippingFee = 0;
    if ($request->filled('carrier_id')) {
        $carrier     = ShippingCarrier::with('zones')->find($request->carrier_id);
        $zone        = $carrier?->zones()->where('province', $request->province)->first();
        $shippingFee = $zone ? $zone->fee : ($carrier?->base_fee ?? 0);
    }

 BẰNG đoạn này (dùng helper riêng + per_km_fee nếu có):

    $shippingFee = $this->resolveShippingFee($request->carrier_id, $request->province);
*/

namespace App\Http\Controllers;

use App\Models\ShippingCarrier;
use Illuminate\Http\Request;

/**
 * Trait / helper methods để thêm vào CheckoutController
 */
trait DynamicShippingFee
{
    /**
     * Tính phí ship động dựa trên carrier và province.
     * Ưu tiên: zone fee → base_fee + per_km_fee (nếu có km) → base_fee
     *
     * @param  int|null    $carrierId  ID của ShippingCarrier
     * @param  string|null $province   Tên tỉnh/thành phố
     * @param  float|null  $distanceKm Khoảng cách km (tuỳ chọn, cho per_km_fee)
     * @return float
     */
    protected function resolveShippingFee(?int $carrierId, ?string $province, ?float $distanceKm = null): float
    {
        if (!$carrierId || !$province) {
            return 0;
        }

        $carrier = ShippingCarrier::with('zones')
            ->where('is_active', true)
            ->find($carrierId);

        if (!$carrier) {
            return 0;
        }

        // Tìm zone theo province (khớp chính xác trước, sau đó khớp region)
        $zone = $carrier->zones()
            ->where('province', $province)
            ->first();

        if ($zone) {
            return (float) $zone->fee;
        }

        // Fallback: base_fee + per_km_fee * distance
        $fee = (float) $carrier->base_fee;

        if ($distanceKm && $carrier->per_km_fee > 0) {
            $fee += $carrier->per_km_fee * $distanceKm;
        }

        return round($fee);
    }

    /**
     * Lấy danh sách carriers + phí ship theo province cho một tỉnh cụ thể.
     * Dùng để hiển thị dropdown carrier ở checkout blade.
     */
    protected function getCarriersWithFee(string $province): array
    {
        return ShippingCarrier::where('is_active', true)
            ->with(['zones' => fn($q) => $q->where('province', $province)])
            ->get()
            ->map(function ($carrier) use ($province) {
                $zone = $carrier->zones->first();
                $fee  = $zone ? $zone->fee : $carrier->base_fee;
                $days = $zone?->estimated_days ?? 3;

                return [
                    'id'             => $carrier->id,
                    'name'           => $carrier->name,
                    'code'           => $carrier->code,
                    'fee'            => $fee,
                    'estimated_days' => $days,
                    'fee_label'      => number_format($fee, 0, ',', '.') . 'đ (' . $days . ' ngày)',
                ];
            })
            ->toArray();
    }
}