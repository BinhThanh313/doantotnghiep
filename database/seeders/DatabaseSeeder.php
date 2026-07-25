<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@electro.vn',
            'email_verified_at' => now(),
                    'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::factory()->create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'email_verified_at' => now(),
                    'password' => Hash::make('password'),
        ]);

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            ProductSpecificationSeeder::class, // sinh thông số kỹ thuật demo cho toàn bộ sản phẩm
            ShippingCarrierSeeder::class, // ← phải trước OrderSeeder
            PaymentMethodSeeder::class,
            OrderSeeder::class,
            VoucherSeeder::class,     // dữ liệu demo mã giảm giá, hiệu lực dài (2 năm)
            FlashSaleSeeder::class,   // dữ liệu demo flash sale, hiệu lực dài (1 năm)
            ReviewSeeder::class,      // dữ liệu demo đánh giá sản phẩm (rải trên toàn bộ sản phẩm active)
            ContactMessageSeeder::class, // dữ liệu demo tin nhắn liên hệ (dùng chung khách hàng demo với ReviewSeeder)
            DemoInsightSeeder::class, // dữ liệu demo cho trang "Gợi ý cho Admin" (restock, bán chậm, giỏ hàng bỏ quên, đánh giá xấu...)
        ]);
    }
}