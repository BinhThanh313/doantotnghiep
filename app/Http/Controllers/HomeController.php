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
        // Tab "Tất cả": 8 sản phẩm mới nhất
        $products = Product::with('category')
                           ->where('is_active', true)
                           ->latest()
                           ->limit(8)
                           ->get();

        // Tab "Hàng mới về": sản phẩm được đánh dấu is_new
        $newArrivals = Product::with('category')
                           ->where('is_active', true)
                           ->where('is_new', true)
                           ->latest()
                           ->limit(8)
                           ->get();

        // Tab "Nổi bật": xếp theo lượt xem, ưu tiên bán chạy nếu view_count bằng nhau
        $featuredProducts = Product::with('category')
                           ->where('is_active', true)
                           ->orderByDesc('view_count')
                           ->orderByDesc('is_bestseller')
                           ->latest()
                           ->limit(8)
                           ->get();

        // 6 sản phẩm bán chạy (is_bestseller = true)
        $bestsellers = Product::with('category')
                            ->where('is_active', true)
                            ->where('is_bestseller', true)
                            ->latest()
                            ->limit(6)
                            ->get();

        // Sản phẩm cho carousel "Tất cả sản phẩm" ở cuối trang
        $exploreProducts = Product::with('category')
                            ->where('is_active', true)
                            ->inRandomOrder()
                            ->limit(10)
                            ->get();

        $categories = Category::withCount('products')->get();

        // Sản phẩm nổi bật cho banner hero (ưu tiên Máy tính bảng đang giảm giá,
        // fallback sang sản phẩm mới nhất nếu danh mục chưa có dữ liệu)
        $heroProduct = Product::with('category')
                            ->where('is_active', true)
                            ->whereHas('category', fn($q) => $q->where('name', 'Máy tính bảng'))
                            ->whereNotNull('original_price')
                            ->latest()
                            ->first()
                        ?? Product::with('category')->where('is_active', true)->latest()->first();

        // Danh mục dùng để trỏ link cho các banner quảng cáo giữa/cuối trang
        $cameraCategory    = Category::where('name', 'Máy ảnh')->first();
        $watchCategory     = Category::where('name', 'Đồng hồ thông minh')->first();
        $headphoneCategory = Category::where('name', 'Tai nghe')->first();

        // Gợi ý cá nhân hóa bằng Item-based Collaborative Filtering (product_similarities):
        // user đăng nhập -> lan điểm từ sản phẩm đã mua/xem sang sản phẩm tương đồng,
        // khách -> fallback rỗng (ẩn block)
        $forYou = Auth::check()
            ? $recommendationService->forUser(Auth::user(), 8)
            : collect();

        return view('home', compact(
            'products', 'newArrivals', 'featuredProducts', 'bestsellers', 'exploreProducts',
            'categories', 'forYou', 'heroProduct', 'cameraCategory', 'watchCategory', 'headphoneCategory'
        ));
    }
}