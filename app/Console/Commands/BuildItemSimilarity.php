<?php

namespace App\Console\Commands;

use App\Services\ItemSimilarityService;
use Illuminate\Console\Command;

/**
 * Tính lại toàn bộ ma trận độ tương đồng sản phẩm (Item-based CF) và lưu
 * vào bảng `product_similarities`. Nên chạy định kỳ (VD: cronjob hàng
 * đêm) vì hành vi user thay đổi liên tục, không tính real-time vì tốn kém.
 *
 * Cách chạy: php artisan recommendation:build-similarity
 */
class BuildItemSimilarity extends Command
{
    protected $signature = 'recommendation:build-similarity {--top=20 : Số sản phẩm tương đồng nhất giữ lại cho mỗi sản phẩm}';

    protected $description = 'Tính lại ma trận độ tương đồng sản phẩm (Item-based Collaborative Filtering)';

    public function handle(ItemSimilarityService $service): int
    {
        $this->info('Đang tính độ tương đồng giữa các sản phẩm dựa trên hành vi user...');

        $stats = $service->build((int) $this->option('top'));

        $this->info("Hoàn tất: {$stats['products']} sản phẩm có dữ liệu tương tác, {$stats['pairs']} cặp tương đồng được lưu.");

        return self::SUCCESS;
    }
}