<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Chỉ phục vụ DEMO trước hội đồng — đảm bảo cả 8 card ở trang "Gợi ý cho
 * Admin" đều có ít nhất 1 dòng dữ liệu.
 *
 * AN TOÀN CHẠY LẠI NHIỀU LẦN (idempotent) và KHÔNG đụng vào order_items /
 * đơn hàng thật — mục #2 (bán chậm) được xử lý bằng cách TẠO 1 sản phẩm
 * demo hoàn toàn mới thay vì chỉnh sửa dữ liệu sản phẩm/đơn hàng có sẵn.
 *
 * Chạy: php artisan db:seed --class=DemoInsightSeeder
 */
class DemoInsightSeeder extends Seeder
{
    private const DEMO_TAG  = 'demo_seed';
    private const DEMO_SLUG = 'demo-slow-moving-tai-nghe-bluetooth';

    public function run(): void
    {
        $this->seedSlowMoving();

        // 4 sản phẩm thật, loại trừ sản phẩm demo vừa tạo ở trên
        $products = Product::where('is_active', true)
            ->where('slug', '!=', self::DEMO_SLUG)
            ->orderBy('id')
            ->take(4)
            ->get();

        if ($products->count() < 4) {
            $this->command?->warn('[Insight demo] Cần ít nhất 4 sản phẩm active để seed đủ các mục demo.');
            return;
        }

        $this->seedRestock($products[0]);
        $this->seedAbandonedCart($products[1]);
        $this->seedIncompleteProduct($products[2]);
        $this->seedNegativeReviews($products[3]);
    }

    /** #1 — 1 sản phẩm tồn kho thấp + lịch sử bán đều trong 30 ngày → sắp hết hàng */
    private function seedRestock(Product $product): void
    {
        InventoryLog::where('product_id', $product->id)
            ->where('notes', self::DEMO_TAG)
            ->delete();

        $product->update(['stock' => 5]);

        for ($i = 0; $i < 12; $i++) {
            $qty = rand(3, 7);
            $log = InventoryLog::create([
                'product_id'      => $product->id,
                'quantity_change' => -$qty,
                'stock_before'    => $product->stock + $qty,
                'stock_after'     => $product->stock,
                'reason'          => 'purchase',
                'notes'           => self::DEMO_TAG,
            ]);

            DB::table('inventory_logs')->where('id', $log->id)->update([
                'created_at' => Carbon::now()->subDays(rand(1, 29)),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->command?->info("[Insight demo] Restock: {$product->name} (id={$product->id})");
    }

    /**
     * #2 — Tạo 1 sản phẩm DEMO hoàn toàn mới, tồn kho cao, không gắn với
     * bất kỳ order_items nào (vì mới tạo) → tự nhiên thoả điều kiện "chưa
     * bán trong 30 ngày qua" mà không cần đụng tới dữ liệu đơn hàng thật.
     * updateOrCreate theo slug cố định nên chạy lại nhiều lần vẫn chỉ có
     * đúng 1 sản phẩm demo, không nhân bản.
     */
    private function seedSlowMoving(): void
    {
        $category = Category::orderBy('id')->first();
        if (!$category) {
            $this->command?->warn('[Insight demo] Không có category nào — bỏ qua mục bán chậm.');
            return;
        }

        $product = Product::updateOrCreate(
            ['slug' => self::DEMO_SLUG],
            [
                'category_id'    => $category->id,
                'name'           => 'Tai nghe Bluetooth ZenAudio X2 (Demo)',
                'description'    => 'Sản phẩm demo phục vụ minh hoạ tính năng gợi ý cho Admin — tồn kho cao, chưa phát sinh đơn hàng nào.',
                'price'          => 590000,
                'original_price' => 690000,
                'stock'          => 60,
                'is_active'      => true,
                'is_new'         => false,
                'is_bestseller'  => false,
            ]
        );

        $this->command?->info("[Insight demo] Slow-moving (sản phẩm demo mới): {$product->name} (id={$product->id})");
    }

    /** #7 — 1 dòng cart_items "cũ" (48h trước) chưa checkout → giỏ hàng bị bỏ quên */
    private function seedAbandonedCart(Product $product): void
    {
        $user = User::where('role', '!=', 'admin')->orderBy('id')->first();
        if (!$user) {
            return;
        }

        $item = CartItem::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            ['quantity' => 1]
        );

        DB::table('cart_items')->where('id', $item->id)->update([
            'created_at' => Carbon::now()->subHours(48),
            'updated_at' => Carbon::now()->subHours(48),
        ]);

        $this->command?->info("[Insight demo] Abandoned cart: {$user->name} / {$product->name}");
    }

    /** #8 — 1 sản phẩm bị xóa ảnh phụ + rút ngắn mô tả < 100 ký tự */
    private function seedIncompleteProduct(Product $product): void
    {
        ProductImage::where('product_id', $product->id)->delete();
        $product->update(['description' => 'Sản phẩm chính hãng, bảo hành 12 tháng.']);

        $this->command?->info("[Insight demo] Incomplete product: {$product->name}");
    }

    /** #9 — 5 review 1-2 sao dồn vào 1 sản phẩm trong 7 ngày gần nhất */
    private function seedNegativeReviews(Product $product): void
    {
        $users = User::where('role', '!=', 'admin')->orderBy('id')->take(5)->get();
        if ($users->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 5; $i++) {
            $user = $users[$i % $users->count()];

            $review = Review::updateOrCreate(
                ['product_id' => $product->id, 'user_id' => $user->id],
                [
                    'rating'            => rand(1, 2),
                    'title'             => 'Không như mong đợi',
                    'comment'           => 'Chất lượng sản phẩm chưa như kỳ vọng, cần cải thiện thêm.',
                    'is_visible'        => true,
                    'verified_purchase' => false,
                ]
            );

            DB::table('reviews')->where('id', $review->id)->update([
                'created_at' => Carbon::now()->subDays(rand(1, 6)),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->command?->info("[Insight demo] Negative reviews: {$product->name}");
    }
}