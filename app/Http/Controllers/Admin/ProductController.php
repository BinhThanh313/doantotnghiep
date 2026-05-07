<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(
            Product::with('category')->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string',
            'price'       => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'stock'       => 'required|integer',
            'image'       => 'nullable|image',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        return response()->json(Product::create($data), 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string',
            'price'       => 'sometimes|numeric',
            'category_id' => 'sometimes|exists:categories,id',
            'stock'       => 'sometimes|integer',
        ]);

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Đã xoá']);
    }
}