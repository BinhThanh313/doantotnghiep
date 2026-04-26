<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        // 1. Tạo dữ liệu Mock cho Categories
        $categories = collect([
            (object)['id' => 1, 'name' => 'Laptops', 'products_count' => 5],
            (object)['id' => 2, 'name' => 'Smartphones', 'products_count' => 8],
            (object)['id' => 3, 'name' => 'Cameras', 'products_count' => 3],
        ]);

        // 2. Tạo dữ liệu Mock cho Products
        $allProducts = collect();
        for ($i = 1; $i <= 12; $i++) {
            $allProducts->push((object)[
                'id' => $i,
                'name' => "Sản phẩm mẫu $i",
                'price' => 100 + ($i * 10),
                'image' => null, // Sẽ dùng ảnh mặc định trong Blade
                'is_new' => $i % 2 == 0,
                'category_id' => ($i % 3) + 1
            ]);
        }

        // 3. Giả lập phân trang (Pagination) để không bị lỗi gọi hàm ->links() ở Blade
        $currentPage = $request->input('page', 1);
        $perPage = 9;
        $currentItems = $allProducts->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $products = new LengthAwarePaginator(
            $currentItems, 
            $allProducts->count(), 
            $perPage, 
            $currentPage, 
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('shop', compact('products', 'categories'));
    }
}