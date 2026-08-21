<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCombo;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    public function index()
    {
        return ProductCombo::with(['product:id,name,image', 'comboProduct:id,name,image'])
            ->latest()
            ->get();
    }

    /**
     * Tạo combo từ 1 gợi ý ở trang Insights (product_a + product_b + score).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'        => 'required|exists:products,id|different:combo_product_id',
            'combo_product_id'  => 'required|exists:products,id',
            'discount_percent'  => 'nullable|integer|min:1|max:99',
            'similarity_score'  => 'nullable|numeric',
        ]);

        // Kiểm tra xem cặp combo này đã tồn tại theo chiều nào chưa (A->B hoặc B->A)
        $existing = ProductCombo::where(function ($q) use ($data) {
            $q->where('product_id', $data['product_id'])
              ->where('combo_product_id', $data['combo_product_id']);
        })->orWhere(function ($q) use ($data) {
            $q->where('product_id', $data['combo_product_id'])
              ->where('combo_product_id', $data['product_id']);
        })->first();

        if ($existing) {
            $existing->update([
                'discount_percent' => $data['discount_percent'] ?? $existing->discount_percent,
                'similarity_score' => $data['similarity_score'] ?? $existing->similarity_score,
                'is_active'        => true,
            ]);

            return response()->json($existing->load(['product:id,name,image', 'comboProduct:id,name,image']), 200);
        }

        $combo = ProductCombo::create([
            'product_id'       => $data['product_id'],
            'combo_product_id' => $data['combo_product_id'],
            'discount_percent' => $data['discount_percent'] ?? 5,
            'similarity_score' => $data['similarity_score'] ?? null,
            'is_active'        => true,
        ]);

        return response()->json($combo->load(['product:id,name,image', 'comboProduct:id,name,image']), 201);
    }

    public function toggle($id)
    {
        $combo = ProductCombo::findOrFail($id);
        $combo->is_active = !$combo->is_active;
        $combo->save();

        return response()->json($combo);
    }

    public function destroy($id)
    {
        ProductCombo::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
