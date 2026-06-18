<?php
// app/Http/Controllers/Admin/FlashSaleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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