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

        $similarByProduct = $this->similarityService->topSimilarBatch($sourceWeights->keys()->all(), 10);

        foreach ($sourceWeights as $productId => $weight) {
            $similar = $similarByProduct[$productId] ?? collect();

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

        // Lấy dư ứng viên (không chỉ đúng $limit) để còn "vốn" bù thêm sau khi
        // lọc is_active, tránh trường hợp vài id trong top bị inactive làm hụt kết quả.
        $topIds = $scores->sortDesc()->take($limit * 3)->keys();

        $products = Product::whereIn('id', $topIds)
            ->where('is_active', true)
            ->with('activeFlashSaleItem')
            ->get()
            ->keyBy('id');

        $results = $topIds->map(fn ($id) => $products[$id] ?? null)
            ->filter()
            ->take($limit)
            ->values();

        if ($results->count() < $limit) {
            $results = $this->topUp($results, $boughtIds, $limit);
        }

        return $results->values();
    }

    /**
     * Bù thêm cho đủ $limit khi kết quả cá nhân hóa (CF) chưa đủ:
     *   1. Ưu tiên sản phẩm bestseller (tín hiệu "đáng tin" nhất khi thiếu dữ liệu cá nhân hóa).
     *   2. Nếu vẫn thiếu, bù tiếp bằng sản phẩm có view_count cao nhất còn lại —
     *      đảm bảo hầu như luôn lấp đầy $limit miễn là catalog còn đủ sản phẩm,
     *      thay vì phụ thuộc hoàn toàn vào cờ is_bestseller (dễ bị quá ít/quá hẹp).
     */
    private function topUp(Collection $results, Collection $boughtIds, int $limit): Collection
    {
        $existingIds = $results->pluck('id')->merge($boughtIds);

        if ($results->count() < $limit) {
            $bestsellers = Product::whereNotIn('id', $existingIds)
                ->where('is_active', true)
                ->where('is_bestseller', true)
                ->orderByDesc('view_count')
                ->limit($limit - $results->count())
                ->with('activeFlashSaleItem')
                ->get();

            $results = $results->concat($bestsellers);
            $existingIds = $existingIds->merge($bestsellers->pluck('id'));
        }

        if ($results->count() < $limit) {
            $popular = Product::whereNotIn('id', $existingIds)
                ->where('is_active', true)
                ->orderByDesc('view_count')
                ->limit($limit - $results->count())
                ->with('activeFlashSaleItem')
                ->get();

            $results = $results->concat($popular);
        }

        return $results;
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
        return $this->topUp(collect(), $excludeIds, $limit)->values();
    }
}