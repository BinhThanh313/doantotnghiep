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
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

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
        $categories = Category::withCount('products')->get();

        // ==================== PHẦN AJAX ====================
        if ($request->ajax() || $request->wantsJson()) {
            return view('shop.products', compact('products'))->render();
        }
        // =================================================

        return view('shop.shop', compact('products', 'categories'));
    }

    public function show($id, RecommendationService $recommendationService)
    {
        $product    = Product::with('category')->findOrFail($id);
        $categories = Category::withCount('products')->get();

        // Tăng lượt xem + ghi log lịch sử xem (dùng cho gợi ý cá nhân hóa)
        $product->increment('view_count');
        ProductView::create([
            'user_id'    => Auth::id(), // <-- Sửa auth()->id() thành Auth::id()
            'session_id' => Auth::check() ? null : session()->getId(), // <-- Sửa auth()->check()
            'product_id' => $product->id,
            'viewed_at'  => now(),
        ]);

        // Gợi ý sản phẩm: liên quan / khách hàng cũng mua / dành riêng cho bạn
        // Sửa auth()->user() thành Auth::user()
        $recommendations = $recommendationService->forProductPage($product, Auth::user());
        return view('shop.show', compact('product', 'categories', 'recommendations'));
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
        $query = Product::with('category')
            ->where('is_active', true)
            ->where('is_bestseller', true);

        $products   = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::withCount('products')->get();

        return view('shop.bestseller', compact('products', 'categories'));
    }
}