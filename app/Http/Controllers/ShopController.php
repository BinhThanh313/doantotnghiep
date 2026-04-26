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

        return view('shop.shop', compact('products', 'categories'));
    }

    public function show($id)
    {
        // 1. Tạo mock data cho danh sách Danh mục (Categories)
        $categories = [
            (object) ['id' => 1, 'name' => 'Điện thoại thông minh', 'products_count' => 15],
            (object) ['id' => 2, 'name' => 'Laptop & Máy tính', 'products_count' => 8],
            (object) ['id' => 3, 'name' => 'Phụ kiện công nghệ', 'products_count' => 24],
            (object) ['id' => 4, 'name' => 'Thiết bị thông minh', 'products_count' => 5],
        ];

        // 2. Tạo mock data cho Sản phẩm (Product) dựa trên ID trên URL
        $product = (object) [
            'id' => $id,
            'name' => 'Sản phẩm thử nghiệm số ' . $id,
            'price' => 199.99,
            'image' => null, // Cố tình để null để View tự lấy ảnh mặc định (product-4.png)
            'description' => 'Đây là nội dung mô tả giả (Mock Data) cho sản phẩm. Bạn có thể thoải mái căn chỉnh giao diện, CSS, HTML. Sau khi hoàn thiện toàn bộ các trang, chúng ta mới bắt đầu kết nối với Database.',
            'category' => (object) ['name' => 'Điện thoại thông minh'] // Giả lập quan hệ (relationship) với bảng Category
        ];

        // 3. Trả về View cùng với Mock Data
        return view('shop.show', compact('product', 'categories'));
    }
}