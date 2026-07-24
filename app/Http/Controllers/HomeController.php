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
        $products = Product::with(['category', 'activeFlashSaleItem'])
                           ->where('is_active', true)
                           ->latest()
                           ->limit(8)
                           ->get();

        $newArrivals = Product::with(['category', 'activeFlashSaleItem'])
                           ->where('is_active', true)
                           ->where('is_new', true)
                           ->latest()
                           ->limit(8)
                           ->get();

        $featuredProducts = Product::with(['category', 'activeFlashSaleItem'])
                           ->where('is_active', true)
                           ->orderByDesc('view_count')
                           ->orderByDesc('is_bestseller')
                           ->latest()
                           ->limit(8)
                           ->get();

        $bestsellers = Product::with(['category', 'activeFlashSaleItem'])
                            ->where('is_active', true)
                            ->where('is_bestseller', true)
                            ->latest()
                            ->limit(6)
                            ->get();

        $exploreProducts = Product::with(['category', 'activeFlashSaleItem'])
                            ->where('is_active', true)
                            ->inRandomOrder()
                            ->limit(10)
                            ->get();

        $categories = Category::withCount('products')->get();

        $heroProduct = Product::with(['category', 'activeFlashSaleItem'])
                            ->where('is_active', true)
                            ->whereHas('category', fn($q) => $q->where('name', 'Máy tính bảng'))
                            ->whereNotNull('original_price')
                            ->latest()
                            ->first()
                        ?? Product::with(['category', 'activeFlashSaleItem'])->where('is_active', true)->latest()->first();

        // Danh mục dùng để trỏ link cho các banner quảng cáo giữa/cuối trang
        // (gộp 3 truy vấn where()->first() riêng lẻ thành 1 truy vấn whereIn)
        $adCategories = Category::whereIn('name', ['Máy ảnh', 'Đồng hồ thông minh', 'Tai nghe'])
                            ->get()
                            ->keyBy('name');

        // Gợi ý cá nhân hóa bằng Item-based Collaborative Filtering (product_similarities):
        // user đăng nhập -> lan điểm từ sản phẩm đã mua/xem sang sản phẩm tương đồng,
        // khách -> fallback rỗng (ẩn block)
        $forYou = Auth::check()
            ? $recommendationService->forUser(Auth::user(), 8)
            : collect();

        return view('home', compact(
            'products', 'newArrivals', 'featuredProducts', 'bestsellers', 'exploreProducts',
            'categories', 'forYou', 'heroProduct'
        ) + [
            'cameraCategory'    => $adCategories->get('Máy ảnh'),
            'watchCategory'     => $adCategories->get('Đồng hồ thông minh'),
            'headphoneCategory' => $adCategories->get('Tai nghe'),
        ]);
    }
}