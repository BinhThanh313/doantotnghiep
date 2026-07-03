<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\ItemBasedRecommendationService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(ItemBasedRecommendationService $recommendationService)
    {
        // 8 sản phẩm mới nhất cho trang chủ
        $products = Product::with('category')
                           ->where('is_active', true)
                           ->latest()
                           ->limit(8)
                           ->get();

        // 6 sản phẩm bán chạy (tạm thời lấy ngẫu nhiên, sau dùng order count)
        $bestsellers = Product::with('category')
                            ->where('is_active', true)
                            ->where('is_bestseller', true)
                            ->latest()
                            ->limit(6)
                            ->get();

        $categories = Category::withCount('products')->get();

        // Gợi ý cá nhân hóa bằng Item-based Collaborative Filtering (product_similarities):
        // user đăng nhập -> lan điểm từ sản phẩm đã mua/xem sang sản phẩm tương đồng,
        // khách -> fallback rỗng (ẩn block)
        $forYou = Auth::check()
            ? $recommendationService->forUser(Auth::user(), 8)
            : collect();

        return view('home', compact('products', 'bestsellers', 'categories', 'forYou'));
    }
}