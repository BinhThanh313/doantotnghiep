<?php

namespace App\Services;

use App\Models\ProductSimilarity;
use Illuminate\Support\Facades\DB;

/**
 * Tính độ tương đồng giữa các sản phẩm dựa trên hành vi user (Item-based
 * Collaborative Filtering), dùng cosine similarity trên ma trận user-item
 * thưa (sparse), lưu kết quả vào bảng `product_similarities` để tra cứu
 * nhanh thay vì tính real-time.
 *
 * Cách tính (không cần thư viện ML, chỉ cần đại số tuyến tính cơ bản):
 *   1. Xây ma trận: mỗi user có 1 vector trọng số cho từng sản phẩm đã
 *      tương tác (mua = 3, xem = 1).
 *   2. Với mỗi cặp sản phẩm (i, j) cùng được 1 user tương tác, cộng dồn
 *      tích trọng số vào "dot product" của cặp đó.
 *   3. cosine(i, j) = dot(i, j) / (||i|| x ||j||)
 *      với ||i|| = căn bậc 2 tổng bình phương trọng số của sản phẩm i
 *      qua tất cả user (Euclidean norm).
 *
 * Độ phức tạp: O(số user x số sản phẩm mỗi user bình phương) — chấp
 * nhận được với quy mô dữ liệu vừa/nhỏ (hàng trăm-nghìn user). Với dữ
 * liệu lớn hơn nhiều, cần chuyển sang thư viện chuyên dụng (Spark ALS,
 * hoặc ma trận thưa của scikit-learn/implicit bên Python).
 */
class ItemSimilarityService
{
    private const WEIGHT_PURCHASE = 3.0;
    private const WEIGHT_VIEW = 1.0;

    /**
     * Tính lại toàn bộ ma trận tương đồng và lưu vào DB (thay thế dữ
     * liệu cũ). Chỉ giữ lại top-N sản phẩm tương đồng nhất cho mỗi sản
     * phẩm để bảng không phình to vô ích.
     *
     * @return array{products: int, pairs: int} thống kê để log/hiển thị
     */
    public function build(int $topKPerProduct = 20): array
    {
        $matrix = $this->loadInteractionMatrix();

        if (empty($matrix)) {
            DB::table('product_similarities')->delete();
            return ['products' => 0, 'pairs' => 0];
        }

        [$dot, $normSquared] = $this->computePairwiseDotProducts($matrix);

        $rows = $this->buildTopKRows($dot, $normSquared, $topKPerProduct);

        DB::transaction(function () use ($rows) {
            DB::table('product_similarities')->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('product_similarities')->insert($chunk);
            }
        });

        return [
            'products' => count($normSquared),
            'pairs'    => count($rows),
        ];
    }

    /**
     * Lấy top-N sản phẩm tương đồng nhất với 1 sản phẩm, từ bảng đã tính sẵn.
     */
    public function topSimilar(int $productId, int $limit = 10)
    {
        return ProductSimilarity::where('product_id', $productId)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /**
     * Bản gộp query của topSimilar() — lấy sản phẩm tương đồng cho NHIỀU
     * sản phẩm nguồn cùng lúc bằng 1 câu query duy nhất (thay vì gọi
     * topSimilar() trong vòng lặp, tốn 1 query/sản phẩm nguồn — N+1).
     * Vì build() chỉ lưu tối đa top-20 similar/sản phẩm, lấy hết rồi group
     * + cắt limit trong PHP vẫn rẻ hơn nhiều so với N round-trip DB.
     *
     * @return array<int, \Illuminate\Support\Collection> [product_id => Collection<ProductSimilarity>]
     */
    public function topSimilarBatch(array $productIds, int $limit = 10): array
    {
        if (empty($productIds)) {
            return [];
        }

        return ProductSimilarity::whereIn('product_id', $productIds)
            ->orderByDesc('score')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->take($limit))
            ->all();
    }

    /**
     * Xây ma trận [user_id => [product_id => trọng_số]] từ đơn hàng đã
     * hoàn tất (mua = 3) và lượt xem có gắn user_id (xem = 1, bỏ qua lượt
     * xem của khách vãng lai vì không có định danh nhất quán để đối chiếu).
     */
    private function loadInteractionMatrix(): array
    {
        $matrix = [];

        $purchases = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.user_id')
            ->where('orders.status', 'completed')
            ->select('orders.user_id', 'order_items.product_id', DB::raw('count(*) as cnt'))
            ->groupBy('orders.user_id', 'order_items.product_id')
            ->get();

        foreach ($purchases as $row) {
            $matrix[$row->user_id][$row->product_id] =
                ($matrix[$row->user_id][$row->product_id] ?? 0) + self::WEIGHT_PURCHASE * $row->cnt;
        }

        $views = DB::table('product_views')
            ->whereNotNull('user_id')
            ->select('user_id', 'product_id', DB::raw('count(*) as cnt'))
            ->groupBy('user_id', 'product_id')
            ->get();

        foreach ($views as $row) {
            $matrix[$row->user_id][$row->product_id] =
                ($matrix[$row->user_id][$row->product_id] ?? 0) + self::WEIGHT_VIEW * $row->cnt;
        }

        return $matrix;
    }

    /**
     * Với mỗi user, duyệt qua tất cả cặp sản phẩm họ từng tương tác và
     * cộng dồn tích trọng số (dot product từng phần) — cách làm chuẩn để
     * tính similarity trên ma trận thưa mà không cần dựng ma trận đầy đủ
     * (n_products x n_products) trong bộ nhớ.
     *
     * @return array{0: array<string, float>, 1: array<int, float>} [dot theo key "i-j", normSquared theo product_id]
     */
    private function computePairwiseDotProducts(array $matrix): array
    {
        $dot = [];
        $normSquared = [];

        foreach ($matrix as $products) {
            foreach ($products as $productId => $weight) {
                $normSquared[$productId] = ($normSquared[$productId] ?? 0) + $weight * $weight;
            }

            $productIds = array_keys($products);
            $count = count($productIds);

            for ($a = 0; $a < $count; $a++) {
                for ($b = $a + 1; $b < $count; $b++) {
                    $i = $productIds[$a];
                    $j = $productIds[$b];
                    $key = $i < $j ? "{$i}-{$j}" : "{$j}-{$i}";
                    $dot[$key] = ($dot[$key] ?? 0) + $products[$i] * $products[$j];
                }
            }
        }

        return [$dot, $normSquared];
    }

    /**
     * Từ dot product + norm, tính cosine similarity cho từng cặp, sau đó
     * với mỗi sản phẩm chỉ giữ lại top-K sản phẩm tương đồng nhất (cả 2
     * chiều i->j và j->i) để chuẩn bị insert vào DB.
     */
    private function buildTopKRows(array $dot, array $normSquared, int $topK): array
    {
        // similarities[product_id] = [similar_product_id => score, ...]
        $similarities = [];

        foreach ($dot as $key => $dotProduct) {
            [$i, $j] = array_map('intval', explode('-', $key));

            $normI = $normSquared[$i] ?? 0;
            $normJ = $normSquared[$j] ?? 0;

            if ($normI <= 0 || $normJ <= 0) {
                continue;
            }

            $cosine = $dotProduct / sqrt($normI * $normJ);

            if ($cosine <= 0) {
                continue;
            }

            $similarities[$i][$j] = $cosine;
            $similarities[$j][$i] = $cosine;
        }

        $now = now();
        $rows = [];

        foreach ($similarities as $productId => $related) {
            arsort($related);
            $top = array_slice($related, 0, $topK, true);

            foreach ($top as $similarProductId => $score) {
                $rows[] = [
                    'product_id'         => $productId,
                    'similar_product_id' => $similarProductId,
                    'score'              => round($score, 6),
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        return $rows;
    }
}