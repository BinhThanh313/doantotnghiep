<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    /**
     * Danh mục kèm số lượng sản phẩm, dùng chung cho index/show/bestsellers.
     */
    private function categoriesWithCounts()
    {
        return \Illuminate\Support\Facades\Cache::remember('shop_categories_with_counts', 3600, fn() => 
            Category::withCount('products')->get()
        );
    }

    public function index(Request $request)
    {
        $query = Product::with(['category', 'activeFlashSaleItem'])->where('is_active', true);

        // Lọc theo danh mục
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Tìm kiếm theo tên
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Lọc theo giá
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sắp xếp
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name'       => $query->orderBy('name', 'asc'),
            default      => $query->latest(),
        };

        $products   = $query->paginate(12)->withQueryString();
        $categories = $this->categoriesWithCounts();

        // ==================== PHẦN AJAX ====================
        if ($request->ajax() || $request->wantsJson()) {
            return view('shop.products', compact('products'))->render();
        }
        // =================================================

        return view('shop.shop', compact('products', 'categories'));
    }

    public function show($id, RecommendationService $recommendationService)
    {
        $product    = Product::with(['category', 'specifications', 'activeFlashSaleItem', 'images', 'variants'])->findOrFail($id);
        $categories = $this->categoriesWithCounts();

        // Tăng lượt xem + ghi log lịch sử xem (dùng cho gợi ý cá nhân hóa)
        $product->increment('view_count');
        ProductView::create([
            'user_id'    => Auth::id(), // <-- Sửa auth()->id() thành Auth::id()
            'session_id' => Auth::check() ? null : session()->getId(), // <-- Sửa auth()->check()
            'product_id' => $product->id,
            'viewed_at'  => now(),
        ]);

        // Gợi ý sản phẩm: liên quan / khách hàng cũng mua / dành riêng cho bạn
        // Caching theo product_id và user_id để tăng tốc độ load trang chi tiết sản phẩm
        $recommendations = \Illuminate\Support\Facades\Cache::remember(
            'product_recommendations_' . $product->id . '_' . (Auth::id() ?? 'guest'),
            3600,
            fn() => $recommendationService->forProductPage($product, Auth::user())
        );

        // Combo do admin tạo (từ gợi ý "thường mua cùng") liên quan tới sản phẩm này
        $combos = \Illuminate\Support\Facades\Cache::remember(
            'product_combos_' . $product->id,
            3600,
            fn() => \App\Models\ProductCombo::activeForProduct($product->id)
        );

        return view('shop.show', compact('product', 'categories', 'recommendations', 'combos'));
    }

    public function vouchers()
    {
        
        $vouchers = \Illuminate\Support\Facades\Cache::remember('shop_vouchers_active', 3600, fn() => 
            Voucher::where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('max_uses')
                        ->orWhereColumn('used_count', '<', 'max_uses');
                })
                ->orderBy('created_at', 'desc')
                ->get()
        );

        // Trả về file view resources/views/shop/vouchers.blade.php
        return view('shop.vouchers', compact('vouchers'));
    }

    public function bestsellers(Request $request)
    {
        $query = Product::with(['category', 'activeFlashSaleItem'])
            ->where('is_active', true)
            ->where('is_bestseller', true);

        $products   = $query->latest()->paginate(12)->withQueryString();
        $categories = $this->categoriesWithCounts();

        // Tab "Tất cả" trong khối "Sản Phẩm Của Chúng Tôi"
        $allProducts = \Illuminate\Support\Facades\Cache::remember('home_products', 3600, fn() => 
            Product::with(['category', 'activeFlashSaleItem'])->where('is_active', true)->latest()->limit(8)->get()
        );

        // Tab "Hàng Mới Về"
        $newArrivals = \Illuminate\Support\Facades\Cache::remember('home_new_arrivals', 3600, fn() => 
            Product::with(['category', 'activeFlashSaleItem'])->where('is_active', true)->where('is_new', true)->latest()->limit(8)->get()
        );

        // Tab "Nổi Bật"
        $featuredProducts = \Illuminate\Support\Facades\Cache::remember('home_featured_products', 3600, fn() => 
            Product::with(['category', 'activeFlashSaleItem'])->where('is_active', true)->orderByDesc('view_count')->orderByDesc('is_bestseller')->latest()->limit(8)->get()
        );

        // Danh mục dùng để trỏ link cho các banner quảng cáo
        $adCategories   = \Illuminate\Support\Facades\Cache::remember('bestseller_ad_categories', 3600, fn() => 
            Category::whereIn('name', ['Máy ảnh', 'Đồng hồ thông minh'])->get()->keyBy('name')
        );
        $cameraCategory = $adCategories->get('Máy ảnh');
        $watchCategory  = $adCategories->get('Đồng hồ thông minh');

        return view('shop.bestseller', compact(
            'products', 'categories', 'allProducts', 'newArrivals', 'featuredProducts',
            'cameraCategory', 'watchCategory'
        ));
    }
}