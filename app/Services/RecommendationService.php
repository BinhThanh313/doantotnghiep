<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service tổng hợp logic gợi ý sản phẩm cá nhân hóa (hybrid recommendation).
 *
 * Kết hợp 3 nguồn tín hiệu:
 *  - Content-based: cùng danh mục / tầm giá với sản phẩm đang xem
 *  - Collaborative filtering (co-purchase): sản phẩm hay xuất hiện chung đơn hàng
 *  - Cá nhân hóa theo user: danh mục ưu thích từ lịch sử mua hàng / lịch sử xem
 *
 * Có cơ chế fallback (cold-start) về sản phẩm bán chạy khi chưa đủ dữ liệu.
 */
class RecommendationService
{
    /**
     * Gói gợi ý đầy đủ dùng cho trang chi tiết sản phẩm.
     */
    public function forProductPage(Product $product, ?User $user): array
    {
        return [
            'related'            => $this->relatedProducts($product),
            'frequently_bought'  => $this->frequentlyBoughtWith($product),
            'for_you'            => $user ? $this->forUser($user) : collect(),
        ];
    }

    /**
     * 1. Sản phẩm liên quan: cùng danh mục hoặc tầm giá tương đồng,
     *    ưu tiên cùng danh mục, bestseller và lượt xem cao.
     */
    public function relatedProducts(Product $product, int $limit = 8): Collection
    {
        return Product::where('id', '!=', $product->id)
            ->where('is_active', true)
            ->where(function ($q) use ($product) {
                $q->where('category_id', $product->category_id)
                  ->orWhereBetween('price', [$product->price * 0.5, $product->price * 1.5]);
            })
            ->orderByRaw('category_id = ? DESC', [$product->category_id])
            ->orderByDesc('is_bestseller')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * 2. "Khách hàng cũng mua" — co-purchase dựa trên order_items:
     *    tìm các sản phẩm hay xuất hiện chung đơn hàng với $product.
     */
    public function frequentlyBoughtWith(Product $product, int $limit = 4): Collection
    {
        $orderIds = OrderItem::where('product_id', $product->id)->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        $productIds = OrderItem::whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $product->id)
            ->select('product_id', DB::raw('count(*) as cnt'))
            ->groupBy('product_id')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get();
    }

    /**
     * 3. Gợi ý cá nhân hóa theo user: ưu tiên danh mục mà user hay mua,
     *    nếu chưa có đơn hàng thì dùng lịch sử xem, nếu vẫn chưa có gì
     *    thì fallback về bestseller (xử lý cold-start).
     */
    public function forUser(User $user, int $limit = 8): Collection
    {
        $boughtIds = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('product_id');

        $topCategoryIds = $this->topCategoriesFromOrders($user);

        if ($topCategoryIds->isEmpty()) {
            $topCategoryIds = $this->topCategoriesFromViews($user);
        }

        $query = Product::whereNotIn('id', $boughtIds)->where('is_active', true);

        if ($topCategoryIds->isNotEmpty()) {
            $query->whereIn('category_id', $topCategoryIds);
        } else {
            // Cold-start: user hoàn toàn mới, chưa có đơn hàng lẫn lượt xem
            $query->where('is_bestseller', true);
        }

        $results = $query->orderByDesc('view_count')->limit($limit)->get();

        // Nếu lọc theo category cho ra quá ít kết quả, bù thêm bằng bestseller
        if ($results->count() < $limit) {
            $existingIds = $results->pluck('id')->merge($boughtIds);
            $extra = Product::whereNotIn('id', $existingIds)
                ->where('is_active', true)
                ->where('is_bestseller', true)
                ->limit($limit - $results->count())
                ->get();
            $results = $results->concat($extra);
        }

        return $results;
    }

    /**
     * 4. Sản phẩm đã xem gần đây (cho user đăng nhập hoặc khách theo session).
     */
    public function recentlyViewed(?User $user, ?string $sessionId, int $limit = 6): Collection
    {
        $query = ProductView::with('product')->orderByDesc('viewed_at');

        $query = $user
            ? $query->where('user_id', $user->id)
            : $query->where('session_id', $sessionId);

        return $query->limit($limit * 3) // lấy dư để loại trùng sản phẩm
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id')
            ->take($limit)
            ->values();
    }

    // ==================== HELPERS ====================

    /**
     * Top 3 danh mục mà user mua nhiều nhất, dựa trên order_items.
     */
    private function topCategoriesFromOrders(User $user): Collection
    {
        return OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.category_id', DB::raw('count(*) as cnt'))
            ->groupBy('products.category_id')
            ->orderByDesc('cnt')
            ->limit(3)
            ->pluck('category_id');
    }

    /**
     * Top 3 danh mục mà user xem nhiều nhất, dựa trên product_views.
     */
    private function topCategoriesFromViews(User $user): Collection
    {
        return ProductView::where('user_id', $user->id)
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->select('products.category_id', DB::raw('count(*) as cnt'))
            ->groupBy('products.category_id')
            ->orderByDesc('cnt')
            ->limit(3)
            ->pluck('category_id');
    }
}