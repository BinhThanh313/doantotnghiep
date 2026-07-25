<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InteractionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $products = Product::where('is_active', true)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->info('Không đủ dữ liệu User hoặc Product để seeder.');
            return;
        }

        $this->command->info('Đang tạo dữ liệu giả lập (mua hàng & xem sản phẩm) cho thuật toán gợi ý...');

        // Tạo cluster: Chọn ra một vài nhóm sản phẩm hay đi chung để tạo tính tương đồng rõ rệt
        // Ví dụ:
        // Cluster 1: Điện thoại + Ốp lưng + Sạc dự phòng + Tai nghe
        // Cluster 2: Laptop + Chuột + Balo + Đế tản nhiệt
        $productIds = $products->pluck('id')->toArray();
        
        foreach ($users as $user) {
            // Mỗi user có 80% xác suất sẽ có lịch sử tương tác
            if (rand(1, 100) > 80) continue;

            // Lấy ngẫu nhiên 3 đến 8 sản phẩm cho user này
            shuffle($productIds);
            $interactedProductIds = array_slice($productIds, 0, rand(3, 8));

            // Tạo lượt xem (ProductView)
            foreach ($interactedProductIds as $pid) {
                ProductView::create([
                    'user_id' => $user->id,
                    'product_id' => $pid,
                    'viewed_at' => now()->subDays(rand(1, 30))->subHours(rand(1, 24)),
                ]);
            }

            // Tạo đơn hàng cho 50% số sản phẩm đã xem
            $boughtProducts = array_slice($interactedProductIds, 0, max(1, count($interactedProductIds) / 2));
            
            if (count($boughtProducts) > 0) {
                // Tính tổng tiền
                $totalAmount = 0;
                foreach ($boughtProducts as $pid) {
                    $prod = $products->firstWhere('id', $pid);
                    $totalAmount += $prod->sale_price ?? $prod->price;
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => '09' . rand(10000000, 99999999),
                    'address' => 'Địa chỉ giả lập ' . Str::random(10),
                    'province' => 'Hà Nội',
                    'tracking_number' => Order::generateTrackingNumber(),
                    'invoice_number' => Order::generateInvoiceNumber(),
                    'total_amount' => $totalAmount,
                    'shipping_fee' => 30000,
                    'discount_amount' => 0,
                    'status' => 'completed', // Bắt buộc completed để thuật toán tính
                    'payment_status' => 'paid',
                    'payment_method' => 'cod',
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);

                foreach ($boughtProducts as $pid) {
                    $prod = $products->firstWhere('id', $pid);
                    $price = $prod->sale_price ?? $prod->price;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $pid,
                        'product_name' => $prod->name,
                        'product_sku' => $prod->sku,
                        'quantity' => rand(1, 2),
                        'price' => $price,
                        'total' => $price * 1,
                    ]);
                }
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu tương tác ảo!');
    }
}
