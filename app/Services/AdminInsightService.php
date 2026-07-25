<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\InventoryLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSimilarity;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gợi ý hành động cho ADMIN (khác với recommendation cho khách hàng).
 * Mỗi method trả về 1 danh sách "insight" độc lập, đều tái sử dụng dữ
 * liệu vận hành sẵn có (đơn hàng, tồn kho, đánh giá, giỏ hàng, độ tương
 * đồng sản phẩm) — không cần bảng dữ liệu mới, trừ #7 (dùng cart_items).
 */
class AdminInsightService
{
    /**
     * #1 — Sản phẩm nên nhập thêm hàng.
     * days_to_out_of_stock = stock hiện tại / doanh số trung bình mỗi ngày (30 ngày gần nhất)
     * Cảnh báo nếu số ngày còn lại < $thresholdDays.
     */
    public function restockRecommendations(int $windowDays = 30, int $thresholdDays = 7, int $limit = 10)
    {
        $since = Carbon::now()->subDays($windowDays);

        $sold = InventoryLog::where('reason', 'purchase')
            ->where('created_at', '>=', $since)
            ->selectRaw('product_id, SUM(-quantity_change) as total_sold')
            ->groupBy('product_id')
            ->pluck('total_sold', 'product_id');

        return Product::where('is_active', true)
            ->where(function ($query) use ($sold) {
                $query->whereIn('id', $sold->keys())
                      ->orWhere(function ($q) {
                          $q->where('stock', '<=', 5)->where('stock', '>', 0);
                      });
            })
            ->get()
            ->map(function ($product) use ($sold, $windowDays) {
                $totalSold      = (int) ($sold[$product->id] ?? 0);
                $avgDailySales  = $totalSold / $windowDays;
                $daysLeft       = $avgDailySales > 0 ? $product->stock / $avgDailySales : null;

                return [
                    'product_id'      => $product->id,
                    'name'            => $product->name,
                    'image'           => $product->image,
                    'stock'           => $product->stock,
                    'sold_last_days'  => $totalSold,
                    'avg_daily_sales' => round($avgDailySales, 2),
                    'days_left'       => $daysLeft !== null ? round($daysLeft, 1) : null,
                ];
            })
            ->filter(fn ($row) => ($row['days_left'] !== null && $row['days_left'] < 7) || ($row['stock'] <= 5 && $row['stock'] > 0))
            ->sortBy(function ($row) {
                return $row['days_left'] ?? 9999;
            })
            ->take($limit)
            ->values();
    }

    /**
     * #2 — Sản phẩm bán chậm, tồn kho cao → đề xuất giảm giá.
     * Không có order_item nào trong $windowDays ngày gần nhất + stock > $minStock.
     */
    public function slowMovingProducts(int $windowDays = 30, int $minStock = 20, int $limit = 10)
    {
        $since = Carbon::now()->subDays($windowDays);

        // Lấy ID các sản phẩm CÓ bán trong thời gian qua (dùng JOIN nhanh hơn whereDoesntHave rất nhiều)
        $soldProductIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $since)
            ->pluck('product_id')
            ->unique()
            ->toArray();

        return Product::where('is_active', true)
            ->where('stock', '>', $minStock)
            ->whereNotIn('id', $soldProductIds)
            ->orderByDesc('stock')
            ->take($limit)
            ->get(['id', 'name', 'image', 'stock', 'price'])
            ->map(fn ($p) => [
                'product_id'         => $p->id,
                'name'               => $p->name,
                'image'              => $p->image,
                'stock'              => $p->stock,
                'price'              => (float) $p->price,
                'suggested_discount' => $p->stock > $minStock * 2 ? 10 : 5,
            ]);
    }

    /**
     * #3 — Sản phẩm đang có xu hướng tăng (doanh số tuần này so với tuần trước).
     */
    public function trendingProducts(int $limit = 10, float $minGrowthPercent = 50)
    {
        $now        = Carbon::now();
        $weekStart  = $now->copy()->subDays(7);
        $prevStart  = $now->copy()->subDays(14);

        $thisWeek = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $weekStart)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $prevWeek = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$prevStart, $weekStart])
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        if ($thisWeek->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $thisWeek->keys())->get()->keyBy('id');

        return $thisWeek->map(function ($qty, $productId) use ($prevWeek, $products) {
                $prevQty = (int) ($prevWeek[$productId] ?? 0);
                $growth  = $prevQty > 0
                    ? round((($qty - $prevQty) / $prevQty) * 100, 1)
                    : ($qty > 0 ? 100 : 0); // sản phẩm mới bán tuần này coi như +100%

                return [
                    'product_id'     => $productId,
                    'name'           => $products[$productId]->name ?? '—',
                    'image'          => $products[$productId]->image ?? null,
                    'qty_this_week'  => (int) $qty,
                    'qty_prev_week'  => $prevQty,
                    'growth_percent' => $growth,
                ];
            })
            ->filter(fn ($row) => $row['growth_percent'] >= $minGrowthPercent)
            ->sortByDesc('growth_percent')
            ->take($limit)
            ->values();
    }

    /**
     * #4 — Sản phẩm nên đẩy mạnh quảng cáo: doanh thu cao + đang bán chạy
     * (không dùng lợi nhuận vì chưa có cột giá vốn trong schema).
     */
    public function advertisingCandidates(int $windowDays = 30, int $limit = 10)
    {
        $since = Carbon::now()->subDays($windowDays);

        $stats = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $since)
            ->where('orders.status', 'completed')
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(order_items.quantity * order_items.price) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->take($limit)
            ->get();

        if ($stats->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $stats->pluck('product_id'))->get()->keyBy('id');

        return $stats->map(fn ($row) => [
            'product_id' => $row->product_id,
            'name'       => $products[$row->product_id]->name ?? '—',
            'image'      => $products[$row->product_id]->image ?? null,
            'qty_sold'   => (int) $row->qty,
            'revenue'    => (float) $row->revenue,
        ]);
    }

    /**
     * #5 — Gợi ý tạo combo: cặp sản phẩm có độ tương đồng cao nhất,
     * tái sử dụng bảng product_similarities đã tính sẵn cho recommendation khách hàng.
     */
    public function comboSuggestions(int $limit = 10, float $minScore = 0.3)
    {
        $rows = ProductSimilarity::with(['product:id,name,image', 'similarProduct:id,name,image'])
            ->where('score', '>=', $minScore)
            ->orderByDesc('score')
            ->take($limit * 4) // Lấy ra một lượng dư để bù trừ các cặp trùng
            ->get();

        $seenPairs = [];
        $result    = collect();

        foreach ($rows as $row) {
            if (!$row->product || !$row->similarProduct) {
                continue;
            }

            // Tránh trùng cặp (A,B) và (B,A)
            $pairKey = collect([$row->product_id, $row->similar_product_id])->sort()->implode('-');
            if (isset($seenPairs[$pairKey])) {
                continue;
            }
            $seenPairs[$pairKey] = true;

            $result->push([
                'product_a' => ['id' => $row->product->id, 'name' => $row->product->name, 'image' => $row->product->image],
                'product_b' => ['id' => $row->similarProduct->id, 'name' => $row->similarProduct->name, 'image' => $row->similarProduct->image],
                'score'     => round($row->score, 3),
            ]);

            if ($result->count() >= $limit) {
                break;
            }
        }

        return $result;
    }

    public function abandonedCarts(int $hoursThreshold = 24, int $limit = 20)
    {
        $cutoff = Carbon::now()->subHours($hoursThreshold);
        $cutoffLower = Carbon::now()->subDays(14); // Quét 14 ngày

        // Đếm tổng số cart_items cũ hơn ngưỡng để tính tỷ lệ
        $totalOldItems = DB::table('cart_items')
            ->whereBetween('created_at', [$cutoffLower, $cutoff])
            ->count();

        if ($totalOldItems === 0) {
            return ['rate' => 0, 'items' => collect()];
        }

        // Truy vấn thuần SQL để tìm giỏ hàng bị bỏ quên
        // (Không có order nào của user đó được tạo ra SAU thời điểm thêm vào giỏ)
        $abandoned = DB::table('cart_items')
            ->join('users', 'cart_items.user_id', '=', 'users.id')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->whereBetween('cart_items.created_at', [$cutoffLower, $cutoff])
            ->leftJoin('orders', function ($join) {
                $join->on('orders.user_id', '=', 'cart_items.user_id')
                     ->on('orders.created_at', '>', 'cart_items.created_at');
            })
            ->whereNull('orders.id')
            ->select(
                'users.name as user_name',
                'users.email as user_email',
                'products.id as product_id',
                'products.name as product_name',
                'cart_items.quantity',
                'cart_items.created_at'
            )
            ->orderByDesc('cart_items.created_at')
            ->limit($limit)
            ->get();

        // Ước tính tỷ lệ bỏ giỏ
        $abandonedCount = DB::table('cart_items')
            ->whereBetween('cart_items.created_at', [$cutoffLower, $cutoff])
            ->leftJoin('orders', function ($join) {
                $join->on('orders.user_id', '=', 'cart_items.user_id')
                     ->on('orders.created_at', '>', 'cart_items.created_at');
            })
            ->whereNull('orders.id')
            ->count();

        $rate = $totalOldItems > 0 ? round($abandonedCount / $totalOldItems * 100, 1) : 0;

        return [
            'rate'  => $rate,
            'items' => $abandoned->map(function ($item) {
                return [
                    'user_name'    => $item->user_name,
                    'user_email'   => $item->user_email,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'added_at'     => $item->created_at,
                    'hours_ago'    => (int) Carbon::parse($item->created_at)->diffInHours(Carbon::now()),
                ];
            })->values(),
        ];
    }

    /**
     * #8 — Sản phẩm thiếu thông tin: không có ảnh phụ hoặc mô tả < 100 ký tự.
     */
    public function incompleteProducts(int $limit = 20)
    {
        return Product::where('is_active', true)
            ->withCount('images')
            ->where(function ($q) {
                $q->doesntHave('images')
                  ->orWhereNull('description')
                  ->orWhereRaw('CHAR_LENGTH(description) < 100');
            })
            ->take($limit)
            ->get()
            ->map(fn ($p) => [
                'product_id'      => $p->id,
                'name'            => $p->name,
                'image'           => $p->image,
                'has_gallery'     => $p->images_count > 0,
                'description_len' => mb_strlen(strip_tags((string) $p->description)),
            ])
            ->values();
    }

    /**
     * #9 — Sản phẩm có nhiều đánh giá xấu gần đây cần kiểm tra chất lượng.
     * Điều kiện: >= $minCount review dưới 3 sao trong $windowDays ngày gần nhất.
     */
    public function negativeReviewAlerts(int $windowDays = 7, int $minCount = 5, int $limit = 10)
    {
        $since = Carbon::now()->subDays($windowDays);

        $rows = Review::where('rating', '<', 3)
            ->where('created_at', '>=', $since)
            ->where('is_visible', true)
            ->selectRaw('product_id, COUNT(*) as bad_count, AVG(rating) as avg_rating')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) >= ?', [$minCount])
            ->orderByDesc('bad_count')
            ->take($limit)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => [
            'product_id' => $row->product_id,
            'name'       => $products[$row->product_id]->name ?? '—',
            'image'      => $products[$row->product_id]->image ?? null,
            'bad_count'  => (int) $row->bad_count,
            'avg_rating' => round((float) $row->avg_rating, 1),
        ]);
    }

    /**
     * Trả về toàn bộ insight cùng lúc — dùng cho endpoint dashboard tổng hợp.
     */
    public function all(): array
    {
        return [
            'restock'          => $this->restockRecommendations(),
            'slow_moving'      => $this->slowMovingProducts(),
            'trending'         => $this->trendingProducts(),
            'advertising'      => $this->advertisingCandidates(),
            'combos'           => $this->comboSuggestions(),
            'abandoned_carts'  => $this->abandonedCarts(),
            'incomplete'       => $this->incompleteProducts(),
            'negative_reviews' => $this->negativeReviewAlerts(),
        ];
    }
}
