<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Services\ItemBasedRecommendationService;
use App\Services\ItemSimilarityService;
use App\Services\RecommendationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Đánh giá và so sánh các phương pháp gợi ý sản phẩm bằng Precision@K,
 * Recall@K, Hit Rate@K, dùng phương pháp "time-based leave-one-out":
 *
 *  - Với mỗi user có >= 2 đơn hàng "completed": đơn hàng GẦN NHẤT được
 *    tách ra làm "ground truth" (test), các đơn còn lại + lượt xem trước
 *    đó là dữ liệu "train".
 *  - Đơn test (và các lượt xem xảy ra sau thời điểm đó) bị loại tạm thời
 *    khỏi DB để RecommendationService không "nhìn thấy" trước khi tính.
 *  - Toàn bộ thao tác nằm trong 1 transaction và LUÔN được rollback ở
 *    cuối, nên không làm mất dữ liệu benchmark đã seed.
 *
 * Cách chạy:
 *   php artisan recommendation:evaluate
 *   php artisan recommendation:evaluate --k=10
 *
 * Cách thêm phương pháp mới để so sánh (VD: Item-based CF sau này):
 *   1. Viết 1 method private trả về Collection<product_id> cho 1 user
 *   2. Thêm vào mảng $this->methods() bên dưới
 */
class EvaluateRecommendations extends Command
{
    protected $signature = 'recommendation:evaluate {--k=8 : Số sản phẩm gợi ý (top-K)}';

    protected $description = 'So sánh các phương pháp gợi ý sản phẩm bằng Precision@K / Recall@K / Hit Rate@K';

    private int $k;

    public function handle(RecommendationService $recommendationService, ItemSimilarityService $similarityService, ItemBasedRecommendationService $itemBasedService): int
    {
        $this->k = (int) $this->option('k');

        $testUserIds = $this->eligibleTestUserIds();

        if ($testUserIds->isEmpty()) {
            $this->error('Không tìm thấy user nào có >= 2 đơn hàng "completed". Hãy chạy RecommendationBenchmarkSeeder trước.');
            return self::FAILURE;
        }

        $this->info("Số user dùng để đánh giá: {$testUserIds->count()} | K = {$this->k}");

        DB::beginTransaction();

        try {
            // 1. Tách test set: xoá tạm đơn hàng gần nhất + view sau đó của từng user
            $groundTruth = $this->splitTrainTest($testUserIds);

            // 1b. Build lại ma trận similarity CHỈ từ dữ liệu train còn lại
            //     (sau khi đã xoá tạm đơn test ở trên) — bắt buộc phải làm
            //     sau bước 1, nếu không Item-based CF sẽ "nhìn thấy" trước
            //     đúng sản phẩm cần đoán (rò rỉ dữ liệu, kết quả bị thổi phồng).
            $this->info('Đang build lại ma trận similarity trên dữ liệu train...');
            $similarityService->build();

            // 2. Chạy từng phương pháp trên phần dữ liệu "train" còn lại và tính điểm
            $methods = $this->methods($recommendationService, $itemBasedService);
            $scores = [];

            foreach ($methods as $methodName => $callback) {
                $scores[$methodName] = $this->evaluateMethod($callback, $groundTruth);
            }

            $this->printResults($scores);
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Lỗi trong quá trình đánh giá: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Luôn rollback: đây chỉ là môi trường đánh giá tạm thời,
        // không được phép làm mất dữ liệu train/test đã seed, kể cả bảng
        // product_similarities vừa build tạm ở bước 1b.
        DB::rollBack();
        $this->info('Đã rollback toàn bộ thay đổi tạm thời — dữ liệu benchmark được giữ nguyên.');

        return self::SUCCESS;
    }

    /**
     * User hợp lệ để đánh giá: có >= 2 đơn hàng "completed"
     * (1 đơn làm test, còn lại làm train).
     */
    private function eligibleTestUserIds()
    {
        return DB::table('orders')
            ->where('status', 'completed')
            ->select('user_id', DB::raw('count(*) as total'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->having('total', '>=', 2)
            ->pluck('user_id');
    }

    /**
     * Với mỗi user: xác định đơn hàng gần nhất làm ground truth, xoá tạm
     * đơn đó (và view phát sinh sau thời điểm đó) khỏi DB.
     *
     * @return array<int, array{product_ids: array<int>}> keyed by user_id
     */
    private function splitTrainTest($userIds): array
    {
        $groundTruth = [];

        foreach ($userIds as $userId) {
            $testOrder = Order::where('user_id', $userId)
                ->where('status', 'completed')
                ->orderByDesc('created_at')
                ->first();

            if (!$testOrder) {
                continue;
            }

            $productIds = OrderItem::where('order_id', $testOrder->id)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->all();

            if (empty($productIds)) {
                continue;
            }

            $groundTruth[$userId] = ['product_ids' => $productIds];

            // Loại bỏ đơn test khỏi dữ liệu train
            OrderItem::where('order_id', $testOrder->id)->delete();
            $testOrder->delete();

            // Loại bỏ các lượt xem xảy ra CÙNG LÚC HOẶC SAU thời điểm đơn
            // test, tránh rò rỉ thông tin tương lai vào lúc tính gợi ý.
            ProductView::where('user_id', $userId)
                ->where('viewed_at', '>=', $testOrder->created_at)
                ->delete();
        }

        return $groundTruth;
    }

    /**
     * Danh sách các phương pháp cần so sánh. Mỗi phương pháp là 1 callback
     * nhận vào $userId và trả về mảng product_id được gợi ý (đã giới hạn K).
     */
    private function methods(RecommendationService $recommendationService, ItemBasedRecommendationService $itemBasedService): array
    {
        $k = $this->k;

        return [
            'Random (baseline)' => function (int $userId) use ($k) {
                return Product::where('is_active', true)
                    ->inRandomOrder()
                    ->limit($k)
                    ->pluck('id')
                    ->all();
            },

            'Bestseller (baseline)' => function (int $userId) use ($k) {
                return Product::where('is_active', true)
                    ->orderByDesc('is_bestseller')
                    ->orderByDesc('view_count')
                    ->limit($k)
                    ->pluck('id')
                    ->all();
            },

            'Rule-based hiện tại (forUser)' => function (int $userId) use ($recommendationService, $k) {
                $user = User::find($userId);
                if (!$user) {
                    return [];
                }
                return $recommendationService->forUser($user, $k)->pluck('id')->all();
            },

            'Rule-based cải tiến (decay + lift + weighted score)' => function (int $userId) use ($recommendationService, $k) {
                $user = User::find($userId);
                if (!$user) {
                    return [];
                }
                return $recommendationService->forUserImproved($user, $k)->pluck('id')->all();
            },

            'Item-based CF (cosine similarity)' => function (int $userId) use ($itemBasedService, $k) {
                $user = User::find($userId);
                if (!$user) {
                    return [];
                }
                return $itemBasedService->forUser($user, $k)->pluck('id')->all();
            },
        ];
    }

    /**
     * Chạy 1 phương pháp cho toàn bộ test user, tính trung bình
     * Precision@K, Recall@K, Hit Rate@K.
     */
    private function evaluateMethod(callable $callback, array $groundTruth): array
    {
        $precisions = [];
        $recalls = [];
        $hits = [];

        foreach ($groundTruth as $userId => $data) {
            $recommended = $callback($userId);
            $relevant = $data['product_ids'];

            $hitCount = count(array_intersect($recommended, $relevant));

            $precisions[] = count($recommended) > 0 ? $hitCount / count($recommended) : 0;
            $recalls[] = count($relevant) > 0 ? min(1, $hitCount / count($relevant)) : 0;
            $hits[] = $hitCount > 0 ? 1 : 0;
        }

        $count = max(1, count($precisions));

        return [
            'precision' => array_sum($precisions) / $count,
            'recall'    => array_sum($recalls) / $count,
            'hit_rate'  => array_sum($hits) / $count,
            'n_users'   => count($precisions),
        ];
    }

    private function printResults(array $scores): void
    {
        $this->newLine();
        $this->info("Kết quả đánh giá (K = {$this->k}):");

        $rows = [];
        foreach ($scores as $method => $s) {
            $rows[] = [
                $method,
                $s['n_users'],
                number_format($s['precision'] * 100, 2) . '%',
                number_format($s['recall'] * 100, 2) . '%',
                number_format($s['hit_rate'] * 100, 2) . '%',
            ];
        }

        $this->table(
            ['Phương pháp', 'Số user test', "Precision@{$this->k}", "Recall@{$this->k}", "Hit Rate@{$this->k}"],
            $rows
        );
    }
}