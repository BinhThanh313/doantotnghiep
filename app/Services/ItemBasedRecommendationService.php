<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Support\HasDecayWeight;
use Illuminate\Support\Collection;

/**
 * Gợi ý cá nhân hóa bằng Item-based Collaborative Filtering:
 *
 *   1. Lấy các sản phẩm user từng tương tác (mua/xem), có decay theo
 *      thời gian giống RecommendationService::forUserImproved().
 *   2. Với mỗi sản phẩm đó, tra bảng `product_similarities` (đã tính
 *      sẵn bởi ItemSimilarityService) để lấy các sản phẩm tương đồng.
 *   3. Cộng dồn điểm: candidate_score += decay(sản_phẩm_nguồn) x similarity.
 *   4. Sắp xếp giảm dần, loại sản phẩm đã mua, fallback bestseller nếu
 *      user mới hoàn toàn (cold-start) hoặc không đủ số lượng.
 */
class ItemBasedRecommendationService
{
    use HasDecayWeight;

    public function __construct(private ItemSimilarityService $similarityService)
    {
    }

    public function forUser(User $user, int $limit = 8): Collection
    {
        $boughtIds = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('product_id');

        $sourceWeights = $this->userInteractionWeights($user);

        if ($sourceWeights->isEmpty()) {
            return $this->bestsellerFallback($boughtIds, $limit);
        }

        $scores = collect();

        foreach ($sourceWeights as $productId => $weight) {
            $similar = $this->similarityService->topSimilar($productId, 10);

            foreach ($similar as $row) {
                if ($boughtIds->contains($row->similar_product_id)) {
                    continue;
                }
                $scores[$row->similar_product_id] =
                    ($scores[$row->similar_product_id] ?? 0) + $weight * $row->score;
            }
        }

        if ($scores->isEmpty()) {
            return $this->bestsellerFallback($boughtIds, $limit);
        }

        $topIds = $scores->sortDesc()->take($limit)->keys();

        $products = Product::whereIn('id', $topIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $results = $topIds->map(fn ($id) => $products[$id] ?? null)->filter()->values();

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
     * Trọng số (đã decay theo thời gian) của từng sản phẩm user từng
     * tương tác — dùng làm "điểm xuất phát" để lan sang sản phẩm tương đồng.
     */
    private function userInteractionWeights(User $user): Collection
    {
        $weights = collect();

        $orders = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->select('product_id', 'created_at')
            ->get();

        foreach ($orders as $row) {
            $w = $this->decayWeight($row->created_at) * 3;
            $weights[$row->product_id] = ($weights[$row->product_id] ?? 0) + $w;
        }

        $views = ProductView::where('user_id', $user->id)
            ->select('product_id', 'viewed_at')
            ->get();

        foreach ($views as $row) {
            $w = $this->decayWeight($row->viewed_at) * 1;
            $weights[$row->product_id] = ($weights[$row->product_id] ?? 0) + $w;
        }

        return $weights;
    }

    private function bestsellerFallback(Collection $excludeIds, int $limit): Collection
    {
        return Product::whereNotIn('id', $excludeIds)
            ->where('is_active', true)
            ->where('is_bestseller', true)
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }
}