<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Models\Product;  
// use App\Models\Category; 

class HomeController extends Controller
{
    public function index()
    {
        // 1. Lấy 8 sản phẩm mới nhất, kèm theo thông tin danh mục của nó
        // $products = Product::with('category')
        //                 ->latest()
        //                 ->take(8)
        //                 ->get();

        // // 2. Lấy tất cả danh mục và đếm xem mỗi danh mục có bao nhiêu sản phẩm
        // $categories = Category::withCount('products')->get();

        // 3. Trả về view 'home' và truyền dữ liệu qua bằng hàm compact
        return view('home', compact('products', 'categories'));
    }
}