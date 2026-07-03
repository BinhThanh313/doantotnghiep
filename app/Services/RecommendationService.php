<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Support\HasDecayWeight;
use Carbon\Carbon;
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
    use HasDecayWeight;

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
     * 2b. "Khách hàng cũng mua" — bản cải tiến dùng LIFT SCORE thay vì đếm
     *     thô, để tránh sản phẩm bán chạy (hot) lấn át sản phẩm thực sự
     *     liên quan chỉ vì nó xuất hiện trong rất nhiều đơn hàng bất kỳ.
     *
     *     lift(A, B) = P(A và B cùng đơn) / (P(A) x P(B))
     *                = (đồng_xuất_hiện x tổng_số_đơn) / (số_đơn_có_A x số_đơn_có_B)
     *
     *     lift > 1: B xuất hiện cùng A nhiều hơn mức ngẫu nhiên kỳ vọng
     *               (liên quan thực sự, không phải vì B đơn giản là hot).
     *     lift = 1: không có mối liên hệ đặc biệt.
     *     lift < 1: xuất hiện cùng nhau ít hơn ngẫu nhiên (không liên quan).
     */
    public function frequentlyBoughtWithLift(Product $product, int $limit = 4, int $minCooccurrence = 2): Collection
    {
        $orderIds = OrderItem::where('product_id', $product->id)->pluck('order_id');
        $countA = $orderIds->count();

        if ($countA === 0) {
            return collect();
        }

        $totalOrders = Order::count();
        if ($totalOrders === 0) {
            return collect();
        }

        // Đồng xuất hiện: với mỗi sản phẩm khác, đếm số đơn chứa CẢ A và nó
        $cooccurrences = OrderItem::whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $product->id)
            ->select('product_id', DB::raw('count(distinct order_id) as cooccurrence'))
            ->groupBy('product_id')
            ->having('cooccurrence', '>=', $minCooccurrence)
            ->pluck('cooccurrence', 'product_id');

        if ($cooccurrences->isEmpty()) {
            return collect();
        }

        // Số đơn có chứa từng sản phẩm B (countB) — tính 1 lần cho tất cả ứng viên
        $countBByProduct = OrderItem::whereIn('product_id', $cooccurrences->keys())
            ->select('product_id', DB::raw('count(distinct order_id) as cnt'))
            ->groupBy('product_id')
            ->pluck('cnt', 'product_id');

        $liftScores = $cooccurrences->map(function ($cooccurrence, $productId) use ($countA, $countBByProduct, $totalOrders) {
            $countB = $countBByProduct[$productId] ?? 0;
            if ($countB === 0) {
                return 0;
            }
            return ($cooccurrence * $totalOrders) / ($countA * $countB);
        })
            ->sortDesc()
            ->take($limit);

        if ($liftScores->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $liftScores->keys())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // Giữ đúng thứ tự theo lift score giảm dần
        return $liftScores->keys()
            ->map(fn ($id) => $products[$id] ?? null)
            ->filter()
            ->values();
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
     * 3b. Gợi ý cá nhân hóa — BẢN CẢI TIẾN, kết hợp nhiều tín hiệu thành
     *     1 điểm số tổng hợp (weighted score) thay vì if/else tuần tự như
     *     forUser():
     *
     *       final_score = 0.45 x category_score   (đã có decay theo thời gian)
     *                   + 0.35 x co_purchase_score (dựa trên lift, không phải đếm thô)
     *                   + 0.20 x popularity_score  (view_count + bestseller)
     *
     *     - Mua/xem GẦN ĐÂY được tính trọng số cao hơn (decay half-life 30 ngày)
     *     - Mua được tính trọng số cao hơn xem (x3) vì tín hiệu mạnh hơn
     *     - Vẫn giữ fallback bestseller cho cold-start như bản gốc
     */
    public function forUserImproved(User $user, int $limit = 8): Collection
    {
        $boughtIds = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('product_id');

        $categoryScores = $this->userWeightedCategoryScores($user);
        $coPurchaseScores = $this->userWeightedCoPurchaseScores($user, $boughtIds);

        if ($categoryScores->isEmpty() && $coPurchaseScores->isEmpty()) {
            // Cold-start: user hoàn toàn mới, chưa có đơn hàng lẫn lượt xem
            return Product::where('is_active', true)
                ->where('is_bestseller', true)
                ->orderByDesc('view_count')
                ->limit($limit)
                ->get();
        }

        $maxCategoryScore = $categoryScores->max() ?: 1;
        $maxCoPurchaseScore = $coPurchaseScores->max() ?: 1;

        $candidateCategoryIds = $categoryScores->keys();
        $candidates = Product::whereNotIn('id', $boughtIds)
            ->where('is_active', true)
            ->where(function ($q) use ($candidateCategoryIds, $coPurchaseScores) {
                if ($candidateCategoryIds->isNotEmpty()) {
                    $q->orWhereIn('category_id', $candidateCategoryIds);
                }
                if ($coPurchaseScores->isNotEmpty()) {
                    $q->orWhereIn('id', $coPurchaseScores->keys());
                }
            })
            ->get();

        $maxViewCount = $candidates->max('view_count') ?: 1;

        $scored = $candidates->map(function (Product $product) use (
            $categoryScores, $maxCategoryScore, $coPurchaseScores, $maxCoPurchaseScore, $maxViewCount
        ) {
            $categoryScore = $maxCategoryScore > 0
                ? ($categoryScores[$product->category_id] ?? 0) / $maxCategoryScore
                : 0;

            $coPurchaseScore = $maxCoPurchaseScore > 0
                ? ($coPurchaseScores[$product->id] ?? 0) / $maxCoPurchaseScore
                : 0;

            $popularityScore = ($product->view_count / $maxViewCount) * 0.7
                + ($product->is_bestseller ? 0.3 : 0);

            $product->recommendation_score = 0.45 * $categoryScore
                + 0.35 * $coPurchaseScore
                + 0.20 * $popularityScore;

            return $product;
        })
            ->sortByDesc('recommendation_score')
            ->values();

        $results = $scored->take($limit);

        // Bù thêm bằng bestseller nếu chưa đủ số lượng yêu cầu
        if ($results->count() < $limit) {
            $existingIds = $results->pluck('id')->merge($boughtIds);
            $extra = Product::whereNotIn('id', $existingIds)
                ->where('is_active', true)
                ->where('is_bestseller', true)
                ->limit($limit - $results->count())
                ->get();
            $results = $results->concat($extra);
        }

        return $results->values();
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

    /**
     * Điểm số theo danh mục cho 1 user, kết hợp mua hàng (trọng số x3) và
     * lượt xem (trọng số x1), mỗi tương tác được nhân thêm decay theo thời
     * gian. Trả về Collection dạng [category_id => score].
     */
    private function userWeightedCategoryScores(User $user): Collection
    {
        $scores = collect();

        $orderInteractions = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.category_id', 'order_items.created_at')
            ->get();

        foreach ($orderInteractions as $row) {
            $weight = $this->decayWeight($row->created_at) * 3; // mua = tín hiệu mạnh
            $scores[$row->category_id] = ($scores[$row->category_id] ?? 0) + $weight;
        }

        $viewInteractions = ProductView::where('user_id', $user->id)
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->select('products.category_id', 'product_views.viewed_at')
            ->get();

        foreach ($viewInteractions as $row) {
            $weight = $this->decayWeight($row->viewed_at) * 1; // xem = tín hiệu nhẹ hơn
            $scores[$row->category_id] = ($scores[$row->category_id] ?? 0) + $weight;
        }

        return $scores->sortDesc();
    }

    /**
     * Điểm số co-purchase cá nhân hóa cho 1 user: với mỗi sản phẩm user đã
     * mua, lấy danh sách sản phẩm liên quan theo LIFT SCORE (không phải đếm
     * thô), nhân thêm decay theo thời điểm mua để đơn gần đây có ảnh hưởng
     * mạnh hơn đơn cũ. Trả về Collection dạng [product_id => score].
     */
    private function userWeightedCoPurchaseScores(User $user, Collection $boughtIds): Collection
    {
        if ($boughtIds->isEmpty()) {
            return collect();
        }

        $purchasedItems = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->select('product_id', 'created_at')
            ->get()
            ->unique('product_id'); // 1 lần tính lift cho mỗi sản phẩm, dù mua nhiều lần

        $scores = collect();

        foreach ($purchasedItems as $item) {
            $product = Product::find($item->product_id);
            if (!$product) {
                continue;
            }

            $related = $this->frequentlyBoughtWithLift($product, limit: 6);
            $decay = $this->decayWeight($item->created_at);

            foreach ($related as $index => $relatedProduct) {
                // related đã được sắp theo lift giảm dần -> vị trí càng cao điểm càng lớn
                $rankWeight = 1 / (1 + $index);
                $scores[$relatedProduct->id] = ($scores[$relatedProduct->id] ?? 0) + ($decay * $rankWeight);
            }
        }

        return $scores->sortDesc();
    }
}