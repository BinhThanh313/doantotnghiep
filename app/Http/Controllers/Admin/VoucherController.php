<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::withCount('usages');

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'code'                 => 'required|string|max:50|unique:vouchers',
        'name'                 => 'nullable|string|max:255',
        'discount_type'        => 'required|in:percent,fixed',
        'discount_value'       => 'required|numeric|min:0',
        'min_amount'           => 'nullable|numeric|min:0',
        'max_discount'         => 'nullable|numeric|min:0',
        'max_uses'             => 'nullable|integer|min:1',
        'max_uses_per_user'    => 'nullable|integer|min:1',
        'start_date'           => 'nullable|date',
        'end_date'             => 'nullable|date|after_or_equal:start_date',
        'applicable_categories'=> 'nullable|array',
        'applicable_products'  => 'nullable|array',
        'is_active'            => 'boolean',
    ]);

    $data['code'] = strtoupper($data['code']);
    $data['is_active'] = $data['is_active'] ?? true;

    // ==================== SỬA DỨT ĐIỂM Ở ĐÂY ====================
    $now = now();

    // Nếu không nhập start_date hoặc start_date ở tương lai → set bằng thời gian hiện tại
    if (empty($data['start_date']) || $data['start_date'] > $now) {
        $data['start_date'] = $now->format('Y-m-d H:i:s');
    }

    // Tự động active nếu start_date đã đến
    if ($data['start_date'] <= $now) {
        $data['is_active'] = true;
    }
    // =========================================================

    $voucher = Voucher::create($data);

    return response()->json($voucher, 201);
}

    public function show($id)
    {
        return response()->json(
            Voucher::withCount('usages')->with('usages.user', 'usages.order')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $data = $request->validate([
            'code'                 => 'sometimes|string|max:50|unique:vouchers,code,' . $id,
            'name'                 => 'nullable|string|max:255',
            'discount_type'        => 'sometimes|in:percent,fixed',
            'discount_value'       => 'sometimes|numeric|min:0',
            'min_amount'           => 'nullable|numeric|min:0',
            'max_discount'         => 'nullable|numeric|min:0',
            'max_uses'             => 'nullable|integer|min:1',
            'max_uses_per_user'    => 'nullable|integer|min:1',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date',
            'applicable_categories'=> 'nullable|array',
            'applicable_products'  => 'nullable|array',
            'is_active'            => 'boolean',
        ]);

        $now = now();

    if (isset($data['start_date'])) {
        if ($data['start_date'] > $now) {
            // Có thể giữ nguyên hoặc cảnh báo, tùy bạn
            // $data['is_active'] = false; // tùy chọn
        } else {
            $data['is_active'] = true;
        }
    }

    if (isset($data['code'])) {
        $data['code'] = strtoupper($data['code']);
    }

    $voucher->update($data);

    return response()->json($voucher);
}

    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);

        if ($voucher->used_count > 0) {
            return response()->json(['message' => 'Không thể xóa voucher đã được sử dụng'], 422);
        }

        $voucher->delete();

        return response()->json(['message' => 'Đã xóa voucher']);
    }

    /**
     * Toggle trạng thái active
     */
    public function toggle($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->update(['is_active' => !$voucher->is_active]);

        return response()->json($voucher);
    }
}
