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
        // Lấy tất cả sản phẩm đang hoạt động kèm theo relations trong 1 query duy nhất để tránh độ trễ mạng (latency)
        $allProducts = Product::with(['category', 'activeFlashSaleItem'])
            ->where('is_active', true)
            ->get();

        // Xử lý dữ liệu hoàn toàn trên RAM bằng Collection của Laravel (rất nhanh)
        $products = $allProducts->sortByDesc('created_at')->take(8);

        $newArrivals = $allProducts->where('is_new', true)
            ->sortByDesc('created_at')->take(8);

        $featuredProducts = $allProducts->sortByDesc('view_count')
            ->sortByDesc('is_bestseller')->take(8);

        $bestsellers = $allProducts->where('is_bestseller', true)
            ->sortByDesc('created_at')->take(6);

        $exploreProducts = $allProducts->count() > 0 
            ? $allProducts->random(min(10, $allProducts->count())) 
            : collect();

        // heroProduct
        $heroProduct = $allProducts->whereNotNull('original_price')
            ->first(function($p) { return $p->category && $p->category->name === 'Máy tính bảng'; }) 
            ?? $allProducts->first();

        // Lấy categories
        $categories = Category::withCount('products')->get();

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