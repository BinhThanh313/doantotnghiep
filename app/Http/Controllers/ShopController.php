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
        return Category::withCount('products')->get();
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

    public function show($id, RecommendationService $recommendationService, \App\Services\ItemBasedRecommendationService $itemBasedRecommendationService)
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
        $recommendations = $recommendationService->forProductPage($product, null); // Pass null here so it doesn't run the old logic
        $recommendations['for_you'] = Auth::check() 
            ? $itemBasedRecommendationService->forUser(Auth::user(), 8)
            : collect();

        // Combo do admin tạo (từ gợi ý "thường mua cùng") liên quan tới sản phẩm này
        $combos = \App\Models\ProductCombo::activeForProduct($product->id);

        return view('shop.show', compact('product', 'categories', 'recommendations', 'combos'));
    }

    public function vouchers()
    {
        
        $vouchers = Voucher::where('is_active', true)
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
            ->get();

        // Trả về file view resources/views/shop/vouchers.blade.php
        return view('shop.vouchers', compact('vouchers'));
    }

    public function bestsellers(Request $request)
    {
        $query = Product::with(['category', 'activeFlashSaleItem'])
            ->where('is_active', true)
            ->where('is_bestseller', true);

        $products   = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::withCount('products')->get();

        $allActive = Product::with(['category', 'activeFlashSaleItem'])
            ->where('is_active', true)
            ->get();

        // Tab "Tất cả" trong khối "Sản Phẩm Của Chúng Tôi"
        $allProducts = $allActive->sortByDesc('created_at')->take(8);

        // Tab "Hàng Mới Về"
        $newArrivals = $allActive->where('is_new', true)->sortByDesc('created_at')->take(8);

        // Tab "Nổi Bật"
        $featuredProducts = $allActive->sortByDesc('view_count')->sortByDesc('is_bestseller')->take(8);

        // Danh mục dùng để trỏ link cho các banner quảng cáo
        $adCategories   = Category::whereIn('name', ['Máy ảnh', 'Đồng hồ thông minh'])->get()->keyBy('name');
        $cameraCategory = $adCategories->get('Máy ảnh');
        $watchCategory  = $adCategories->get('Đồng hồ thông minh');

        return view('shop.bestseller', compact(
            'products', 'categories', 'allProducts', 'newArrivals', 'featuredProducts',
            'cameraCategory', 'watchCategory'
        ));
    }
}