<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCarrier;
use App\Models\ShippingZone;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    // ==================== CARRIERS ====================

    public function carriers()
    {
        return response()->json(ShippingCarrier::with('zones')->get());
    }

    public function storeCarrier(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'code'       => 'required|string|max:50|unique:shipping_carriers',
            'api_key'    => 'nullable|string',
            'api_url'    => 'nullable|url',
            'is_active'  => 'boolean',
            'base_fee'   => 'required|numeric|min:0',
            'per_km_fee' => 'nullable|numeric|min:0',
        ]);

        return response()->json(ShippingCarrier::create($data), 201);
    }

    public function updateCarrier(Request $request, $id)
    {
        $carrier = ShippingCarrier::findOrFail($id);
        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'is_active'  => 'boolean',
            'base_fee'   => 'sometimes|numeric|min:0',
            'per_km_fee' => 'nullable|numeric|min:0',
        ]);
        $carrier->update($data);
        return response()->json($carrier->load('zones'));
    }

    // ==================== ZONES (Phí ship theo tỉnh) ====================

    public function zones($carrierId)
    {
        $carrier = ShippingCarrier::findOrFail($carrierId);
        return response()->json($carrier->zones()->orderBy('province')->get());
    }

    public function storeZone(Request $request, $carrierId)
    {
        ShippingCarrier::findOrFail($carrierId);

        $data = $request->validate([
            'province'       => 'required|string|max:100',
            'region'         => 'nullable|in:north,central,south',
            'fee'            => 'required|numeric|min:0',
            'estimated_days' => 'nullable|integer|min:1',
        ]);
        $data['carrier_id'] = $carrierId;

        return response()->json(ShippingZone::create($data), 201);
    }

    public function updateZone(Request $request, $id)
    {
        $zone = ShippingZone::findOrFail($id);
        $data = $request->validate([
            'fee'            => 'sometimes|numeric|min:0',
            'estimated_days' => 'nullable|integer|min:1',
        ]);
        $zone->update($data);
        return response()->json($zone);
    }

    public function destroyZone($id)
    {
        ShippingZone::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xóa khu vực']);
    }

    // ==================== SHIPMENTS ====================

    public function shipments(Request $request)
    {
        $query = Shipment::with('order', 'carrier');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function updateShipment(Request $request, $id)
    {
        $shipment = Shipment::with('order')->findOrFail($id);

        $data = $request->validate([
            'carrier_id'          => 'sometimes|exists:shipping_carriers,id',
            'tracking_number'     => 'nullable|string|max:100',
            'status'              => 'sometimes|in:pending,in_transit,delivered,failed,returned',
            'estimated_delivery'  => 'nullable|date',
            'actual_delivery'     => 'nullable|date',
            'notes'               => 'nullable|string',
        ]);

        $shipment->update($data);

        // Khi giao hàng thành công → cập nhật trạng thái order
        if (isset($data['status']) && $data['status'] === 'delivered') {
            $shipment->order->update([
                'status'         => 'delivered',
                'payment_status' => $shipment->order->payment_method === 'COD' ? 'paid' : $shipment->order->payment_status,
            ]);
        }

        return response()->json($shipment->load('order', 'carrier'));
    }

    // ==================== TÍNH PHÍ SHIP (Public API) ====================

    /**
     * Tính phí ship theo province (gọi từ checkout)
     */
    public function calculateFee(Request $request)
    {
        $request->validate([
            'province'   => 'required|string',
            'carrier_id' => 'nullable|exists:shipping_carriers,id',
        ]);

        $province = $request->province;

        if ($request->filled('carrier_id')) {
            $carrier = ShippingCarrier::with('zones')->findOrFail($request->carrier_id);
            $fee = $carrier->getFeeForProvince($province);
            $zone = $carrier->zones()->where('province', $province)->first();

            return response()->json([
                'carrier'        => $carrier->name,
                'fee'            => $fee,
                'estimated_days' => $zone?->estimated_days ?? 3,
            ]);
        }

        // Trả về phí của tất cả carriers
        $carriers = ShippingCarrier::where('is_active', true)->with('zones')->get();

        $results = $carriers->map(function ($carrier) use ($province) {
            $zone = $carrier->zones()->where('province', $province)->first();
            return [
                'carrier_id'     => $carrier->id,
                'carrier'        => $carrier->name,
                'carrier_code'   => $carrier->code,
                'fee'            => $zone ? $zone->fee : $carrier->base_fee,
                'estimated_days' => $zone?->estimated_days ?? 3,
            ];
        });

        return response()->json($results);
    }
}
