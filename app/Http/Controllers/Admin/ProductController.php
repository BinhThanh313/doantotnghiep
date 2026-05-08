<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate(15);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'category_id'    => 'required|exists:categories,id',
            'price'          => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'is_new'         => 'boolean',
            'is_active'      => 'boolean',
            'image'          => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                                     ->store('products', 'public');
        }

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        $product = Product::create($data);

        return response()->json($product->load('category'), 201);
    }

    public function show($id)
    {
        return response()->json(
            Product::with('category')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'category_id'    => 'sometimes|exists:categories,id',
            'price'          => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'is_new'         => 'boolean',
            'is_active'      => 'boolean',
            'image'          => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')
                                     ->store('products', 'public');
        }

        $product->update($data);

        return response()->json($product->load('category'));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Đã xóa sản phẩm']);
    }
}