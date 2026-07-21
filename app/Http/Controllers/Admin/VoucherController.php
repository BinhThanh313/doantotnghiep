<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    /**
     * POST /api/admin/vouchers/import
     * Nhập hàng loạt voucher từ file Excel/CSV.
     * - Nếu "code" đã tồn tại (không phân biệt hoa thường) => cập nhật voucher đó.
     * - Nếu chưa có => tạo voucher mới.
     *
     * Cột nhận diện (không phân biệt hoa thường, có dấu/không dấu đều OK):
     *   code | ma | maco                        (bắt buộc, duy nhất)
     *   name | ten
     *   discount_type | loaigiam        (percent | fixed — mặc định percent)
     *   discount_value | giatrigiam     (bắt buộc)
     *   min_amount | dontoithieu
     *   max_discount | giamtoida
     *   max_uses | soluottoida
     *   max_uses_per_user | soluotmoinguoi
     *   start_date | ngaybatdau         (định dạng Y-m-d hoặc do Excel tự nhận)
     *   end_date | ngayketthuc
     *   is_active | kichhoat            (1/0, true/false, có/không)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $path = $request->file('file')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Không đọc được file: ' . $e->getMessage()], 422);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return response()->json(['message' => 'File rỗng'], 422);
        }

        $rawHeader = array_shift($rows);
        $aliasMap = [
            'code' => 'code', 'ma' => 'code', 'maco' => 'code', 'mavoucher' => 'code',
            'name' => 'name', 'ten' => 'name', 'tenvoucher' => 'name',
            'discounttype' => 'discount_type', 'loaigiam' => 'discount_type',
            'discountvalue' => 'discount_value', 'giatrigiam' => 'discount_value',
            'minamount' => 'min_amount', 'dontoithieu' => 'min_amount',
            'maxdiscount' => 'max_discount', 'giamtoida' => 'max_discount',
            'maxuses' => 'max_uses', 'soluottoida' => 'max_uses',
            'maxusesperuser' => 'max_uses_per_user', 'soluotmoinguoi' => 'max_uses_per_user',
            'startdate' => 'start_date', 'ngaybatdau' => 'start_date',
            'enddate' => 'end_date', 'ngayketthuc' => 'end_date',
            'isactive' => 'is_active', 'kichhoat' => 'is_active',
        ];

        $header = [];
        foreach ($rawHeader as $col) {
            $key = Str::slug((string) $col, '');
            $header[] = $aliasMap[$key] ?? $key;
        }

        $created = 0;
        $updated = 0;
        $errors  = [];

        DB::transaction(function () use ($rows, $header, &$created, &$updated, &$errors) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = array_combine($header, array_pad($row, count($header), null));

                $code = strtoupper(trim((string) ($data['code'] ?? '')));
                if ($code === '') {
                    continue; // dòng trống bỏ qua
                }

                $discountValue = $data['discount_value'] ?? null;
                if ($discountValue === null || $discountValue === '') {
                    $errors[] = "Dòng {$rowNumber}: Voucher '{$code}' thiếu giá trị giảm (discount_value), đã bỏ qua.";
                    continue;
                }

                $discountType = strtolower(trim((string) ($data['discount_type'] ?? 'percent')));
                if (!in_array($discountType, ['percent', 'fixed'])) {
                    $discountType = 'percent';
                }

                $isActiveRaw = $data['is_active'] ?? null;
                $isActive = $isActiveRaw === null || $isActiveRaw === ''
                    ? true
                    : in_array(strtolower(trim((string) $isActiveRaw)), ['1', 'true', 'có', 'co', 'yes', 'x']);

                $payload = [
                    'name'                  => $data['name'] ?? null,
                    'discount_type'         => $discountType,
                    'discount_value'        => (float) $discountValue,
                    'min_amount'            => !empty($data['min_amount']) ? (float) $data['min_amount'] : 0,
                    'max_discount'          => !empty($data['max_discount']) ? (float) $data['max_discount'] : null,
                    'max_uses'              => !empty($data['max_uses']) ? (int) $data['max_uses'] : null,
                    'max_uses_per_user'     => !empty($data['max_uses_per_user']) ? (int) $data['max_uses_per_user'] : 1,
                    'start_date'            => !empty($data['start_date']) ? $data['start_date'] : now()->format('Y-m-d H:i:s'),
                    'end_date'              => !empty($data['end_date']) ? $data['end_date'] : null,
                    'is_active'             => $isActive,
                ];

                $voucher = Voucher::whereRaw('UPPER(code) = ?', [$code])->first();

                if ($voucher) {
                    $voucher->update($payload);
                    $updated++;
                } else {
                    $payload['code'] = $code;
                    Voucher::create($payload);
                    $created++;
                }
            }
        });

        return response()->json([
            'message' => "Đã nhập xong: {$created} voucher mới, {$updated} voucher cập nhật.",
            'created' => $created,
            'updated' => $updated,
            'errors'  => $errors,
        ]);
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