<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Voucher;

class HomeController extends Controller
{
    public function index()
    {
        // 8 sản phẩm mới nhất cho trang chủ
        $products = Product::with('category')
                           ->where('is_active', true)
                           ->latest()
                           ->limit(8)
                           ->get();

        // 6 sản phẩm bán chạy (tạm thời lấy ngẫu nhiên, sau dùng order count)
        $bestsellers = Product::where('is_active', true)
                              ->inRandomOrder()
                              ->limit(6)
                              ->get();

        $categories = Category::withCount('products')->get();

        return view('home', compact('products', 'bestsellers', 'categories'));
    }
}