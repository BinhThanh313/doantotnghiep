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
            'discount_percent'  => 'nullable|integer|min:1|max:50',
            'similarity_score'  => 'nullable|numeric',
        ]);

        $combo = ProductCombo::firstOrCreate(
            [
                'product_id'       => $data['product_id'],
                'combo_product_id' => $data['combo_product_id'],
            ],
            [
                'discount_percent' => $data['discount_percent'] ?? 5,
                'similarity_score' => $data['similarity_score'] ?? null,
                'is_active'        => true,
            ]
        );

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
