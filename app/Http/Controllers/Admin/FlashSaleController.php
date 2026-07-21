<?php
// app/Http/Controllers/Admin/FlashSaleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FlashSaleController extends Controller
{
    // GET /api/admin/flash-sales
    public function index(Request $request)
    {
        $query = FlashSale::withCount('items')->latest();

        if ($request->filled('status')) {
            $now = now();
            match ($request->status) {
                'running'  => $query->where('is_active', true)
                                    ->where('starts_at', '<=', $now)
                                    ->where('ends_at', '>=', $now),
                'upcoming' => $query->where('is_active', true)
                                    ->where('starts_at', '>', $now),
                'ended'    => $query->where('ends_at', '<', $now),
                'disabled' => $query->where('is_active', false),
                default    => null,
            };
        }

        $sales = $query->paginate(15);

        // Gắn thêm status label
        $sales->getCollection()->transform(function ($s) {
            $s->status = $s->status; // trigger accessor
            $s->seconds_remaining = $s->seconds_remaining;
            return $s;
        });

        return response()->json($sales);
    }

    // POST /api/admin/flash-sales
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at'   => 'required|date',
            'ends_at'     => 'required|date|after:starts_at',
            'is_active'   => 'boolean',
        ]);

        $sale = FlashSale::create($data);

        return response()->json($sale, 201);
    }

    // GET /api/admin/flash-sales/{id}
    public function show($id)
    {
        $sale = FlashSale::with([
            'items.product:id,name,image,price,stock',
        ])->findOrFail($id);

        $sale->status            = $sale->status;
        $sale->seconds_remaining = $sale->seconds_remaining;

        return response()->json($sale);
    }

    // PUT /api/admin/flash-sales/{id}
    public function update(Request $request, $id)
    {
        $sale = FlashSale::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'starts_at'   => 'sometimes|date',
            'ends_at'     => 'sometimes|date|after:starts_at',
            'is_active'   => 'boolean',
        ]);

        $sale->update($data);

        return response()->json($sale);
    }

    // DELETE /api/admin/flash-sales/{id}
    public function destroy($id)
    {
        FlashSale::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xóa Flash Sale']);
    }

    // ── Items ──────────────────────────────────────────────────

    // POST /api/admin/flash-sales/{id}/items
    public function addItem(Request $request, $id)
    {
        $sale = FlashSale::findOrFail($id);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sale_price' => 'required|numeric|min:0',
            'qty_limit'  => 'nullable|integer|min:1',
            'is_active'  => 'boolean',
        ]);

        // Kiểm tra trùng
        if ($sale->items()->where('product_id', $data['product_id'])->exists()) {
            return response()->json(['message' => 'Sản phẩm đã có trong Flash Sale này'], 422);
        }

        $data['flash_sale_id'] = $id;
        $item = FlashSaleItem::create($data);

        return response()->json($item->load('product:id,name,price,image,stock'), 201);
    }

    // POST /api/admin/flash-sales/{id}/items/import
    /**
     * Thêm hàng loạt sản phẩm vào 1 Flash Sale từ file Excel/CSV.
     * Cột nhận diện (không phân biệt hoa thường, có dấu/không dấu đều OK):
     *   product | sanpham | tensanpham   (bắt buộc — so khớp theo tên, không phân biệt hoa thường)
     *   sale_price | giaflashsale | giaban   (bắt buộc)
     *   qty_limit | soluonggioihan | soluong (tùy chọn — để trống = không giới hạn)
     *
     * Sản phẩm không tìm thấy hoặc đã có sẵn trong Flash Sale này sẽ bị bỏ qua
     * và ghi vào errors, không dừng cả file.
     */
    public function importItems(Request $request, $id)
    {
        $sale = FlashSale::findOrFail($id);

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
            'product' => 'product', 'sanpham' => 'product', 'tensanpham' => 'product',
            'saleprice' => 'sale_price', 'giaflashsale' => 'sale_price', 'giaban' => 'sale_price', 'gia' => 'sale_price',
            'qtylimit' => 'qty_limit', 'soluonggioihan' => 'qty_limit', 'soluong' => 'qty_limit',
        ];

        $header = [];
        foreach ($rawHeader as $col) {
            $key = \Illuminate\Support\Str::slug((string) $col, '');
            $header[] = $aliasMap[$key] ?? $key;
        }

        $created = 0;
        $errors  = [];

        DB::transaction(function () use ($rows, $header, $sale, &$created, &$errors) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = array_combine($header, array_pad($row, count($header), null));

                $productName = trim((string) ($data['product'] ?? ''));
                if ($productName === '') {
                    continue; // dòng trống bỏ qua
                }

                $salePrice = $data['sale_price'] ?? null;
                if ($salePrice === null || $salePrice === '') {
                    $errors[] = "Dòng {$rowNumber}: Sản phẩm '{$productName}' thiếu giá Flash Sale (sale_price), đã bỏ qua.";
                    continue;
                }

                $product = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($productName)])->first();
                if (!$product) {
                    $errors[] = "Dòng {$rowNumber}: Không tìm thấy sản phẩm '{$productName}', đã bỏ qua.";
                    continue;
                }

                if ($sale->items()->where('product_id', $product->id)->exists()) {
                    $errors[] = "Dòng {$rowNumber}: '{$productName}' đã có trong Flash Sale này, đã bỏ qua.";
                    continue;
                }

                FlashSaleItem::create([
                    'flash_sale_id' => $sale->id,
                    'product_id'    => $product->id,
                    'sale_price'    => (float) $salePrice,
                    'qty_limit'     => !empty($data['qty_limit']) ? (int) $data['qty_limit'] : null,
                    'is_active'     => true,
                ]);

                $created++;
            }
        });

        return response()->json([
            'message' => "Đã thêm {$created} sản phẩm vào Flash Sale.",
            'created' => $created,
            'errors'  => $errors,
        ]);
    }

    // PUT /api/admin/flash-sales/{saleId}/items/{itemId}
    public function updateItem(Request $request, $saleId, $itemId)
    {
        $item = FlashSaleItem::where('flash_sale_id', $saleId)->findOrFail($itemId);

        $data = $request->validate([
            'sale_price' => 'sometimes|numeric|min:0',
            'qty_limit'  => 'nullable|integer|min:1',
            'is_active'  => 'boolean',
        ]);

        $item->update($data);

        return response()->json($item->load('product:id,name,price,image,stock'));
    }

    // DELETE /api/admin/flash-sales/{saleId}/items/{itemId}
    public function removeItem($saleId, $itemId)
    {
        $item = FlashSaleItem::where('flash_sale_id', $saleId)->findOrFail($itemId);
        $item->delete();

        return response()->json(['message' => 'Đã xóa sản phẩm khỏi Flash Sale']);
    }

    // GET /api/admin/flash-sales/{id}/available-products
    // Danh sách sản phẩm chưa có trong Flash Sale (để thêm mới)
    public function availableProducts($id)
    {
        $existingIds = FlashSaleItem::where('flash_sale_id', $id)
                                    ->pluck('product_id');

        $products = Product::whereNotIn('id', $existingIds)
                           ->where('is_active', true)
                           ->select('id', 'name', 'price', 'stock', 'image')
                           ->orderBy('name')
                           ->get();

        return response()->json($products);
    }
}