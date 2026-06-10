<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;
use App\Models\Order;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Lấy user IDs thực tế
        $userIds  = DB::table('users')->pluck('id')->toArray();
        if (empty($userIds)) $userIds = [null];

        // Lấy product IDs thực tế
        $products = DB::table('products')->select('id', 'name', 'price', 'stock')->get();
        if ($products->isEmpty()) {
            $this->command->warn('Không có sản phẩm, bỏ qua OrderSeeder.');
            return;
        }

        // Lấy carrier IDs (nếu có)
        $carrierIds = DB::table('shipping_carriers')->pluck('id')->toArray();

        $statuses        = ['pending', 'processing', 'ready_to_ship', 'shipped', 'delivered', 'completed', 'completed', 'completed', 'cancelled'];
        $paymentMethods  = ['cod', 'bank', 'cod', 'cod']; // COD phổ biến hơn
        $paymentStatuses = ['unpaid', 'paid', 'paid', 'paid'];
        $provinces       = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng', 'Bình Dương', 'Đồng Nai', 'Hà Nam'];

        $totalOrders = 200; // Tạo 200 đơn hàng

        $this->command->info("Bắt đầu tạo {$totalOrders} đơn hàng...");

        for ($i = 0; $i < $totalOrders; $i++) {
            // Phân phối ngày tạo: 70% trong 30 ngày qua, 30% trong 1 năm qua
            $daysAgo   = rand(0, 100) < 70 ? rand(0, 30) : rand(31, 365);
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $status        = $faker->randomElement($statuses);
            $paymentMethod = $faker->randomElement($paymentMethods);
            $province      = $faker->randomElement($provinces);

            // Payment status logic
            $paymentStatus = match ($status) {
                'completed', 'delivered', 'shipped' => 'paid',
                'cancelled' => $faker->randomElement(['unpaid', 'refunded']),
                default     => $faker->randomElement(['unpaid', 'paid']),
            };

            // Chọn ngẫu nhiên 1-4 sản phẩm
            $selectedProducts = $products->random(min(rand(1, 4), $products->count()));
            $totalAmount      = 0;
            $orderItemsData   = [];

            foreach ($selectedProducts as $product) {
                $qty   = rand(1, 5);
                $price = $product->price;
                $totalAmount += $price * $qty;

                $orderItemsData[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $qty,
                    'price'        => $price,
                ];
            }

            $shippingFee    = $faker->randomElement([0, 15000, 20000, 25000, 30000, 35000]);
            $discountAmount = rand(0, 100) < 30 ? rand(1, 5) * 10000 : 0; // 30% đơn có giảm giá
            $trackingNumber = Order::generateTrackingNumber();
            $invoiceNumber  = Order::generateInvoiceNumber();

            // Insert order
            $orderId = DB::table('orders')->insertGetId([
                'user_id'         => $faker->randomElement($userIds),
                'customer_name'   => $faker->name,
                'customer_email'  => $faker->email,
                'customer_phone'  => $faker->phoneNumber,
                'address'         => $faker->streetAddress,
                'province'        => $province,
                'total_amount'    => $totalAmount,
                'shipping_fee'    => $shippingFee,
                'discount_amount' => $discountAmount,
                'status'          => $status,
                'payment_status'  => $paymentStatus,
                'payment_method'  => $paymentMethod,
                'tracking_number' => $trackingNumber,
                'invoice_number'  => $invoiceNumber,
                'notes'           => rand(0, 100) < 20 ? $faker->sentence(6) : null,
                'return_status'   => 'none',
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt->copy()->addMinutes(rand(0, 120)),
            ]);

            // Insert order_items
            foreach ($orderItemsData as &$item) {
                $item['order_id']   = $orderId;
                $item['created_at'] = $createdAt;
                $item['updated_at'] = $createdAt;
            }
            DB::table('order_items')->insert($orderItemsData);

            // Sửa thành
            if ($paymentStatus === 'paid') {
                DB::table('payments')->insert([
                    'order_id'         => $orderId,
                    'amount'           => $totalAmount + $shippingFee - $discountAmount,
                    'payment_method'   => strtoupper($paymentMethod), // ← đúng tên cột + uppercase
                    'status'           => 'success',                  // ← đúng enum value
                    'transaction_id'   => strtoupper($faker->bothify('TXN-########')),
                    'created_at'       => $createdAt->copy()->addMinutes(rand(1, 30)),
                    'updated_at'       => $createdAt->copy()->addMinutes(rand(1, 30)),
                ]);
            }

            // Insert shipment record (nếu đã vận chuyển)
            if (in_array($status, ['shipped', 'delivered', 'completed']) && !empty($carrierIds)) {
                DB::table('shipments')->insert([
    'order_id'           => $orderId,
    'carrier_id'         => $faker->randomElement($carrierIds),
    'tracking_number'    => $trackingNumber,
    'status'             => match ($status) {
        'shipped'   => 'in_transit',
        'delivered' => 'delivered',
        'completed' => 'delivered',
        default     => 'pending',
    },
    'estimated_delivery' => $createdAt->copy()->addDays(rand(2, 5)),
    'created_at'         => $createdAt,
    'updated_at'         => $createdAt,
]);
            }

            // Progress log
            if (($i + 1) % 50 === 0) {
                $this->command->info("  → Đã tạo " . ($i + 1) . "/{$totalOrders} đơn hàng");
            }
        }

        $this->command->info("✅ Hoàn thành! Đã tạo {$totalOrders} đơn hàng với items, payments, shipments.");
    }
}