<?php

namespace Database\Seeders;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder tạo dữ liệu có XU HƯỚNG RÕ RÀNG (khác với OrderSeeder random thuần túy),
 * để khi demo/bảo vệ đồ án, phần "Gợi ý dành riêng cho bạn" và "Khách hàng cũng
 * mua" thể hiện được kết quả có ý nghĩa thay vì ngẫu nhiên khó giải thích.
 *
 * Chạy riêng: php artisan db:seed --class=RecommendationDemoSeeder
 * (chạy SAU khi đã có DatabaseSeeder / OrderSeeder)
 */
class RecommendationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = DB::table('users')->where('email', 'test@example.com')->first();

        if (!$demoUser) {
            $this->command->warn('Không tìm thấy user test@example.com. Bỏ qua RecommendationDemoSeeder.');
            return;
        }

        // 1. Chọn 1 danh mục "ưa thích" cố định cho demo user, ví dụ Laptop
        $favoriteCategory = DB::table('categories')->where('name', 'Laptop')->first()
            ?? DB::table('categories')->first();

        if (!$favoriteCategory) {
            $this->command->warn('Không có category nào. Bỏ qua.');
            return;
        }

        $favoriteProducts = DB::table('products')
            ->where('category_id', $favoriteCategory->id)
            ->limit(4)
            ->get();

        if ($favoriteProducts->isEmpty()) {
            $this->command->warn('Danh mục demo không có sản phẩm. Bỏ qua.');
            return;
        }

        // 2. Tạo 3 đơn hàng ĐÃ HOÀN THÀNH cho demo user, chỉ mua trong favoriteCategory
        //    => forUser() sẽ nhận ra category này là "top category" của user
        for ($i = 0; $i < 3; $i++) {
            $createdAt = Carbon::now()->subDays(rand(5, 60));
            $product   = $favoriteProducts->random();

            $orderId = DB::table('orders')->insertGetId([
                'user_id'         => $demoUser->id,
                'customer_name'   => $demoUser->name,
                'customer_email'  => $demoUser->email,
                'customer_phone'  => '0900000000',
                'address'         => '123 Đường Demo',
                'province'        => 'TP. Hồ Chí Minh',
                'total_amount'    => $product->price,
                'shipping_fee'    => 20000,
                'discount_amount' => 0,
                'status'          => 'completed',
                'payment_status'  => 'paid',
                'payment_method'  => 'cod',
                'tracking_number' => Order::generateTrackingNumber(),
                'invoice_number'  => Order::generateInvoiceNumber(),
                'return_status'   => 'none',
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);

            DB::table('order_items')->insert([
                'order_id'     => $orderId,
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'quantity'     => 1,
                'price'        => $product->price,
                'created_at'   => $createdAt,
                'updated_at'   => $createdAt,
            ]);
        }

        // 3. Ghi vài lượt xem gần đây cho demo user (phục vụ "Sản phẩm đã xem gần đây")
        foreach ($favoriteProducts->take(3) as $p) {
            DB::table('product_views')->insert([
                'user_id'    => $demoUser->id,
                'session_id' => null,
                'product_id' => $p->id,
                'viewed_at'  => Carbon::now()->subHours(rand(1, 48)),
            ]);
        }

        // 4. Tạo mẫu "đồng mua" rõ ràng: 5 đơn hàng ngẫu nhiên khác đều mua
        //    chung 2 sản phẩm cố định trong favoriteCategory => frequentlyBoughtWith() có kết quả
        if ($favoriteProducts->count() >= 2) {
            [$productA, $productB] = [$favoriteProducts[0], $favoriteProducts[1]];
            $otherUserIds = DB::table('users')->where('id', '!=', $demoUser->id)->pluck('id');

            for ($i = 0; $i < 5; $i++) {
                $createdAt = Carbon::now()->subDays(rand(1, 90));

                $orderId = DB::table('orders')->insertGetId([
                    'user_id'         => $otherUserIds->isNotEmpty() ? $otherUserIds->random() : null,
                    'customer_name'   => 'Khách demo ' . ($i + 1),
                    'customer_email'  => "demo{$i}@example.com",
                    'customer_phone'  => '0911111111',
                    'address'         => '456 Đường Demo',
                    'province'        => 'Hà Nội',
                    'total_amount'    => $productA->price + $productB->price,
                    'shipping_fee'    => 20000,
                    'discount_amount' => 0,
                    'status'          => 'completed',
                    'payment_status'  => 'paid',
                    'payment_method'  => 'cod',
                    'tracking_number' => Order::generateTrackingNumber(),
                    'invoice_number'  => Order::generateInvoiceNumber(),
                    'return_status'   => 'none',
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt,
                ]);

                DB::table('order_items')->insert([
                    [
                        'order_id'     => $orderId,
                        'product_id'   => $productA->id,
                        'product_name' => $productA->name,
                        'quantity'     => 1,
                        'price'        => $productA->price,
                        'created_at'   => $createdAt,
                        'updated_at'   => $createdAt,
                    ],
                    [
                        'order_id'     => $orderId,
                        'product_id'   => $productB->id,
                        'product_name' => $productB->name,
                        'quantity'     => 1,
                        'price'        => $productB->price,
                        'created_at'   => $createdAt,
                        'updated_at'   => $createdAt,
                    ],
                ]);
            }
        }

        $this->command->info("✅ Đã tạo dữ liệu demo gợi ý cá nhân hóa cho user test@example.com (danh mục: {$favoriteCategory->name}).");
    }
}