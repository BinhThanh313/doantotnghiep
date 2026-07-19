<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/admin/products/{productId}/variants
    // ──────────────────────────────────────────────────────────

    public function index(int $productId)
    {
        Product::findOrFail($productId);

        // KHÔNG dùng $product->variants() vì relation đó đã lọc sẵn
        // is_active = true (dùng cho storefront) — admin cần thấy TẤT CẢ
        // biến thể (kể cả đã tắt "Hoạt động") để còn bật lại/sửa được.
        $variants = ProductVariant::withTrashed()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get();

        return response()->json($variants);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/admin/products/{productId}/variants
    // ──────────────────────────────────────────────────────────

    public function store(Request $request, int $productId)
    {
        Product::findOrFail($productId);

        $data = $request->validate([
            'sku'            => 'nullable|string|max:100|unique:product_variants,sku',
            'name'           => 'required|string|max:255',
            'attributes'     => 'nullable|string|max:500',
            'price'          => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock'          => 'required|integer|min:0',
            'image'          => 'nullable|image|max:2048', // file ảnh thật, không phải chuỗi đường dẫn
            'image_url'      => 'nullable|url|max:2048',   // hoặc dán URL ảnh từ ngoài (VD: link ảnh Bing/Google/CDN)
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products/variants', 'public');
        } elseif (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
        } else {
            unset($data['image']);
        }
        unset($data['image_url']);

        $data['product_id'] = $productId;
        $data['is_active']  = $data['is_active'] ?? true;

        $variant = ProductVariant::create($data);

        // Ghi inventory log nếu nhập kho ban đầu > 0
        if ($variant->stock > 0) {
            InventoryLog::create([
                'product_id'      => $productId,
                'variant_id'      => $variant->id,
                'quantity_change' => $variant->stock,
                'stock_before'    => 0,
                'stock_after'     => $variant->stock,
                'reason'          => 'restock',
                'reference_type'  => 'manual',
                'notes'           => 'Tạo mới biến thể',
                'created_by'      => $request->user()?->id,
            ]);
        }

        return response()->json($variant, 201);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/admin/products/{productId}/variants/{id}
    // ──────────────────────────────────────────────────────────

    public function update(Request $request, int $productId, int $id)
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);

        $data = $request->validate([
            'sku'            => 'nullable|string|max:100|unique:product_variants,sku,' . $id,
            'name'           => 'sometimes|string|max:255',
            'attributes'     => 'nullable|string|max:500',
            'price'          => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock'          => 'sometimes|integer|min:0',
            'image'          => 'nullable|image|max:2048', // file ảnh thật, không phải chuỗi đường dẫn
            'image_url'      => 'nullable|url|max:2048',   // hoặc dán URL ảnh từ ngoài
            'remove_image'   => 'nullable|boolean',
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($variant->image && !is_external_image_url($variant->image)) {
                Storage::disk('public')->delete($variant->image);
            }
            $data['image'] = $request->file('image')->store('products/variants', 'public');
        } elseif (!empty($data['image_url'])) {
            if ($variant->image && !is_external_image_url($variant->image)) {
                Storage::disk('public')->delete($variant->image);
            }
            $data['image'] = $data['image_url'];
        } elseif ($request->boolean('remove_image')) {
            if ($variant->image && !is_external_image_url($variant->image)) {
                Storage::disk('public')->delete($variant->image);
            }
            $data['image'] = null;
        } else {
            // Không gửi ảnh mới và không yêu cầu xoá -> giữ nguyên ảnh hiện tại
            unset($data['image']);
        }
        unset($data['remove_image'], $data['image_url']);

        // Nếu stock thay đổi → ghi inventory log
        if (isset($data['stock']) && (int) $data['stock'] !== $variant->stock) {
            $diff = (int) $data['stock'] - $variant->stock;
            InventoryLog::create([
                'product_id'      => $productId,
                'variant_id'      => $variant->id,
                'quantity_change' => $diff,
                'stock_before'    => $variant->stock,
                'stock_after'     => (int) $data['stock'],
                'reason'          => $diff > 0 ? 'restock' : 'adjustment',
                'reference_type'  => 'manual',
                'notes'           => 'Admin cập nhật tồn kho',
                'created_by'      => $request->user()?->id,
            ]);
        }

        $variant->update($data);

        return response()->json($variant);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/admin/products/{productId}/variants/{id}
    // ──────────────────────────────────────────────────────────

    public function destroy(int $productId, int $id)
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);
        $variant->delete();

        return response()->json(['message' => 'Đã xóa biến thể']);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/admin/products/{productId}/variants/{id}/adjust-stock
    // body: { quantity_change, reason, notes }
    // ──────────────────────────────────────────────────────────

    public function adjustStock(Request $request, int $productId, int $id)
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);

        $data = $request->validate([
            'quantity_change' => 'required|integer|not_in:0',
            'reason'          => 'required|in:restock,adjustment,return,damage',
            'notes'           => 'nullable|string|max:500',
        ]);

        $newStock = $variant->stock + $data['quantity_change'];

        if ($newStock < 0) {
            return response()->json([
                'message' => "Tồn kho sau điều chỉnh ({$newStock}) không thể âm",
            ], 422);
        }

        DB::transaction(function () use ($variant, $data, $newStock, $request, $productId) {
            InventoryLog::create([
                'product_id'      => $productId,
                'variant_id'      => $variant->id,
                'quantity_change' => $data['quantity_change'],
                'stock_before'    => $variant->stock,
                'stock_after'     => $newStock,
                'reason'          => $data['reason'],
                'reference_type'  => 'manual',
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $request->user()?->id,
            ]);

            $variant->update(['stock' => $newStock]);
        });

        return response()->json([
            'message'   => 'Điều chỉnh tồn kho thành công',
            'stock'     => $newStock,
            'variant'   => $variant->fresh(),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/admin/products/{productId}/variants/{id}/logs
    // ──────────────────────────────────────────────────────────

    public function logs(int $productId, int $id)
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);

        $logs = InventoryLog::where('variant_id', $variant->id)
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}