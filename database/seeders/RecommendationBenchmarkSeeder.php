<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder sinh dữ liệu hành vi (mua hàng + lượt xem) theo mô hình "persona"
 * để có đủ tín hiệu thống kê cho việc đánh giá/so sánh các thuật toán gợi ý
 * (rule-based cũ, rule-based cải tiến, Item-based CF...) bằng Precision@K,
 * Recall@K, Hit Rate@K.
 *
 * Khác với RecommendationDemoSeeder (chỉ phục vụ demo trực quan cho 1 user),
 * seeder này tạo NHIỀU user với xu hướng hành vi rõ ràng nhưng có nhiễu
 * (noise) để dữ liệu giống thực tế, đồng thời trải dài theo thời gian để
 * có thể áp dụng time-based train/test split.
 *
 * Chạy: php artisan db:seed --class=RecommendationBenchmarkSeeder
 * Seeder tự dọn dữ liệu benchmark cũ (email dạng bench_*) trước khi chạy lại,
 * nên có thể chạy nhiều lần an toàn.
 */
class RecommendationBenchmarkSeeder extends Seeder
{
    /** Tổng số user giả lập sẽ tạo */
    private int $userCount = 80;

    /** Trong số đó, tỉ lệ user KHÔNG có hành vi gì (test cold-start) */
    private float $coldStartRatio = 0.1;

    /** Số ngày lịch sử hành vi trải dài về quá khứ */
    private int $historyDays = 90;

    /** Khoảng số lượt xem / số đơn hàng cho mỗi user có hành vi */
    private array $viewsPerUserRange = [8, 25];
    private array $ordersPerUserRange = [2, 6];

    /** Xác suất một lượt tương tác rơi vào category chính / phụ / ngẫu nhiên */
    private float $primaryWeight = 0.65;
    private float $secondaryWeight = 0.25;
    // phần còn lại (0.10) là ngẫu nhiên hoàn toàn

    public function run(): void
    {
        $categories = DB::table('categories')->pluck('id')->values();

        if ($categories->count() < 2) {
            $this->command->warn('Cần ít nhất 2 category để tạo persona có ý nghĩa. Bỏ qua.');
            return;
        }

        $productsByCategory = DB::table('products')
            ->where('is_active', true)
            ->get(['id', 'category_id', 'price'])
            ->groupBy('category_id');

        // Loại các category không có sản phẩm active
        $categories = $categories->filter(fn ($cid) => isset($productsByCategory[$cid]) && $productsByCategory[$cid]->count() > 0)->values();

        if ($categories->count() < 2) {
            $this->command->warn('Không đủ category có sản phẩm active. Bỏ qua.');
            return;
        }

        $this->cleanupPreviousBenchmarkData();

        $personas = $this->buildPersonas($categories);

        $this->command->info("Bắt đầu tạo {$this->userCount} user benchmark với " . count($personas) . " persona...");

        $totalOrders = 0;
        $totalItems = 0;
        $totalViews = 0;
        $coldStartUsers = 0;

        for ($i = 1; $i <= $this->userCount; $i++) {
            $userId = DB::table('users')->insertGetId([
                'name'              => "Bench User {$i}",
                'email'             => "bench_user_{$i}@example.com",
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'role'              => 'user',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // Một phần user cố tình để trống hành vi -> kiểm tra nhánh cold-start
            $isColdStart = (mt_rand() / mt_getrandmax()) < $this->coldStartRatio;
            if ($isColdStart) {
                $coldStartUsers++;
                continue;
            }

            $persona = $personas[($i - 1) % count($personas)];

            $stats = $this->generateBehaviorForUser($userId, $persona, $productsByCategory, $categories);

            $totalOrders += $stats['orders'];
            $totalItems  += $stats['items'];
            $totalViews  += $stats['views'];
        }

        $this->command->info('Hoàn tất:');
        $this->command->info("  - User có hành vi: " . ($this->userCount - $coldStartUsers));
        $this->command->info("  - User cold-start (không hành vi): {$coldStartUsers}");
        $this->command->info("  - Tổng đơn hàng: {$totalOrders}");
        $this->command->info("  - Tổng order_items: {$totalItems}");
        $this->command->info("  - Tổng product_views: {$totalViews}");
        $this->command->info('Dữ liệu trải dài ' . $this->historyDays . ' ngày, sẵn sàng cho train/test split theo thời gian.');
    }

    /**
     * Xoá dữ liệu benchmark cũ (user "bench_user_*" và toàn bộ đơn hàng/lượt
     * xem liên quan) để có thể chạy lại seeder nhiều lần mà không bị trùng.
     */
    private function cleanupPreviousBenchmarkData(): void
    {
        $oldUserIds = DB::table('users')
            ->where('email', 'like', 'bench_user_%@example.com')
            ->pluck('id');

        if ($oldUserIds->isEmpty()) {
            return;
        }

        $this->command->info("Dọn dẹp {$oldUserIds->count()} user benchmark cũ...");

        $oldOrderIds = DB::table('orders')->whereIn('user_id', $oldUserIds)->pluck('id');
        DB::table('order_items')->whereIn('order_id', $oldOrderIds)->delete();
        DB::table('orders')->whereIn('id', $oldOrderIds)->delete();
        DB::table('product_views')->whereIn('user_id', $oldUserIds)->delete();
        DB::table('users')->whereIn('id', $oldUserIds)->delete();
    }

    /**
     * Sinh danh sách persona: mỗi persona có 1 category chính và 1 category
     * phụ (category kế tiếp theo vòng tròn), để hành vi có xu hướng rõ ràng
     * nhưng không tuyệt đối 1 category duy nhất.
     */
    private function buildPersonas($categories): array
    {
        $personas = [];
        $n = $categories->count();

        foreach ($categories as $index => $categoryId) {
            $personas[] = [
                'primary'   => $categoryId,
                'secondary' => $categories[($index + 1) % $n],
            ];
        }

        return $personas;
    }

    /**
     * Sinh lượt xem + đơn hàng cho 1 user theo persona, trải dài theo thời
     * gian với xu hướng "gần đây khớp persona nhiều hơn quá khứ" để phục vụ
     * việc kiểm chứng cải tiến time-decay.
     */
    private function generateBehaviorForUser(int $userId, array $persona, $productsByCategory, $categories): array
    {
        $viewCount  = rand($this->viewsPerUserRange[0], $this->viewsPerUserRange[1]);
        $orderCount = rand($this->ordersPerUserRange[0], $this->ordersPerUserRange[1]);

        // ---------- Lượt xem ----------
        $viewRows = [];
        for ($v = 0; $v < $viewCount; $v++) {
            $recency = $v / max(1, $viewCount - 1); // 0 = cũ nhất, 1 = mới nhất
            $product = $this->pickProduct($persona, $productsByCategory, $categories, $recency);
            if (!$product) {
                continue;
            }

            $viewRows[] = [
                'user_id'    => $userId,
                'session_id' => null,
                'product_id' => $product->id,
                'viewed_at'  => $this->randomTimestampInHistory($recency),
            ];
        }
        if (!empty($viewRows)) {
            DB::table('product_views')->insert($viewRows);
        }

        // ---------- Đơn hàng ----------
        $itemsTotal = 0;
        for ($o = 0; $o < $orderCount; $o++) {
            $recency = $o / max(1, $orderCount - 1);
            $product = $this->pickProduct($persona, $productsByCategory, $categories, $recency);
            if (!$product) {
                continue;
            }

            $createdAt = $this->randomTimestampInHistory($recency);
            $quantity  = rand(1, 2);
            $price     = $product->price;

            $orderId = DB::table('orders')->insertGetId([
                'user_id'         => $userId,
                'customer_name'   => "Bench User",
                'customer_email'  => "bench_user_{$userId}@example.com",
                'customer_phone'  => '0900000000',
                'address'         => 'Địa chỉ demo benchmark',
                'province'        => 'TP. Hồ Chí Minh',
                'invoice_number'  => 'BENCH-' . $userId . '-' . $o . '-' . uniqid(),
                'total_amount'    => $price * $quantity,
                'shipping_fee'    => 0,
                'discount_amount' => 0,
                // Trạng thái "completed" để được RecommendationService tính
                // vào lịch sử mua hàng (OrderItem::whereHas('order', ...))
                'status'          => 'completed',
                'payment_status'  => 'paid',
                'payment_method'  => 'cod',
                'return_status'   => 'none',
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);

            DB::table('order_items')->insert([
                'order_id'     => $orderId,
                'product_id'   => $product->id,
                'product_name' => 'Sản phẩm benchmark',
                'quantity'     => $quantity,
                'price'        => $price,
                'created_at'   => $createdAt,
                'updated_at'   => $createdAt,
            ]);

            $itemsTotal++;
        }

        return [
            'views'  => count($viewRows),
            'orders' => $orderCount,
            'items'  => $itemsTotal,
        ];
    }

    /**
     * Chọn 1 sản phẩm theo persona. $recency càng gần 1 (càng mới) thì xác
     * suất khớp category chính càng cao, mô phỏng sở thích "rõ nét dần" theo
     * thời gian — dữ liệu để kiểm chứng cải tiến trọng số theo thời gian.
     */
    private function pickProduct(array $persona, $productsByCategory, $categories, float $recency)
    {
        // Xác suất khớp persona tăng dần theo độ mới (0.5 -> 0.9)
        $matchProbability = 0.5 + 0.4 * $recency;
        $roll = mt_rand() / mt_getrandmax();

        if ($roll < $matchProbability * $this->primaryWeight) {
            $categoryId = $persona['primary'];
        } elseif ($roll < $matchProbability * ($this->primaryWeight + $this->secondaryWeight)) {
            $categoryId = $persona['secondary'];
        } else {
            $categoryId = $categories->random();
        }

        $products = $productsByCategory[$categoryId] ?? null;
        if (!$products || $products->isEmpty()) {
            // fallback: category chính nếu category chọn ngẫu nhiên rỗng
            $products = $productsByCategory[$persona['primary']] ?? null;
        }

        return $products && $products->isNotEmpty() ? $products->random() : null;
    }

    /**
     * Sinh timestamp ngẫu nhiên trong quá khứ, thiên về gần hiện tại hơn khi
     * $recency (0-1) càng lớn — dùng cho cả views lẫn orders để giữ đồng bộ
     * trình tự thời gian trong toàn bộ lịch sử hành vi của user.
     */
    private function randomTimestampInHistory(float $recency): Carbon
    {
        // Chia lịch sử thành historyDays ngày, recency=0 -> gần đầu khoảng
        // (cũ nhất), recency=1 -> gần cuối khoảng (mới nhất), có nhiễu ngẫu
        // nhiên +/- vài ngày để tránh các mốc thời gian bị "đều tăm tắp".
        $daysAgo = (int) round($this->historyDays * (1 - $recency));
        $noise   = rand(-3, 3);
        $daysAgo = max(0, min($this->historyDays, $daysAgo + $noise));

        return Carbon::now()
            ->subDays($daysAgo)
            ->subHours(rand(0, 23))
            ->subMinutes(rand(0, 59));
    }
}