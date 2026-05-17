<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN'); // Dùng Faker tiếng Việt
        $orders = [];

        // Các trạng thái đơn hàng có thể có
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $paymentMethods = ['cod', 'bank'];

        // Tạo 50 đơn hàng giả lập
        for ($i = 0; $i < 50; $i++) {
            // Random ngày tạo trong khoảng 7 ngày qua (để biểu đồ hiển thị đẹp)
            $randomDaysAgo = rand(0, 7);
            $createdAt = Carbon::now()->subDays($randomDaysAgo)->subHours(rand(1, 23));

            // Trạng thái (ưu tiên 'completed' nhiều hơn để có doanh thu)
            $status = $faker->randomElement(['pending', 'processing', 'completed', 'completed', 'completed', 'cancelled']);

            $orders[] = [
                'user_id' => rand(1, 5), // Giả sử bạn có user từ ID 1 đến 5
                'customer_name' => $faker->name,
                'customer_email' => $faker->email,
                'customer_phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'total_amount' => rand(100, 5000) * 1000, // Giá từ 100k đến 5 triệu
                'status' => $status,
                'payment_method' => $faker->randomElement($paymentMethods),
                'notes' => $faker->realText(50),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        // Chèn dữ liệu vào bảng orders
        DB::table('orders')->insert($orders);
    }
}