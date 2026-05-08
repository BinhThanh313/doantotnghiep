<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            Category::withCount('products')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $category->update($data);
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Không thể xóa danh mục đang có sản phẩm'
            ], 422);
        }

        $category->delete();
        return response()->json(['message' => 'Đã xóa danh mục']);
    }
}