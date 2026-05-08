<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = DB::table('categories')->pluck('id')->toArray();

        $products = [
            ['iPhone 15 Pro Max', 34990000, 36990000],
            ['Samsung Galaxy S24 Ultra', 29990000, 32990000],
            ['Xiaomi 14 Pro', 19990000, 22990000],
            ['OPPO Find X7', 17990000, null],
            ['Vivo X100 Pro', 18990000, 20990000],
            ['MacBook Pro M3', 54990000, 59990000],
            ['Dell XPS 15', 42990000, 45990000],
            ['ASUS ROG Zephyrus', 38990000, null],
            ['Lenovo ThinkPad X1', 35990000, 38990000],
            ['HP Spectre x360', 32990000, 34990000],
            ['iPad Pro M2', 28990000, null],
            ['Samsung Galaxy Tab S9', 22990000, 25990000],
            ['Xiaomi Pad 6 Pro', 12990000, 14990000],
            ['Apple Watch Ultra 2', 21990000, 23990000],
            ['Samsung Galaxy Watch 6', 8990000, 9990000],
            ['Garmin Fenix 7', 17990000, null],
            ['Sony WH-1000XM5', 8490000, 9490000],
            ['AirPods Pro 2', 6990000, 7490000],
            ['Bose QuietComfort 45', 7990000, 8990000],
            ['Sony Alpha A7 IV', 62990000, null],
            ['Canon EOS R6 Mark II', 58990000, 62990000],
            ['Fujifilm X-T5', 42990000, 45990000],
            ['GoPro Hero 12', 11990000, 12990000],
            ['DJI Osmo Action 4', 8990000, null],
            ['Samsung QLED 4K 55"', 22990000, 25990000],
            ['Sony Bravia XR 65"', 35990000, null],
            ['LG OLED C3 55"', 32990000, 35990000],
            ['JBL Charge 5', 3990000, 4490000],
            ['Sony SRS-XB43', 4490000, null],
            ['Marshall Emberton II', 3290000, 3690000],
            ['iPhone 14', 22990000, 25990000],
            ['Samsung Galaxy A54', 9990000, 11990000],
            ['Realme GT 5', 13990000, null],
            ['MacBook Air M2', 32990000, 35990000],
            ['Lenovo IdeaPad 5 Pro', 21990000, 23990000],
            ['iPad Air M1', 18990000, 20990000],
            ['Apple Watch Series 9', 11990000, 13990000],
            ['Xiaomi Watch 2 Pro', 5990000, null],
            ['Samsung Galaxy Buds2 Pro', 4990000, 5490000],
            ['Jabra Evolve2 75', 9990000, null],
            ['Nikon Z6 III', 55990000, 59990000],
            ['Canon EOS 90D', 38990000, null],
            ['Insta360 X3', 10990000, 11990000],
            ['TCL QLED 50"', 12990000, 14990000],
            ['Panasonic OLED 55"', 28990000, null],
            ['JBL PartyBox 310', 8990000, 9990000],
            ['Bose SoundLink Flex', 3690000, 3990000],
            ['iPhone 13', 17990000, 19990000],
            ['ASUS ZenBook 14', 24990000, null],
            ['Samsung Galaxy Z Fold5', 45990000, 49990000],
        ];

        foreach ($products as $index => [$name, $price, $originalPrice]) {
            DB::table('products')->insert([
                'category_id'    => $categoryIds[array_rand($categoryIds)],
                'name'           => $name,
                'slug'           => Str::slug($name) . '-' . ($index + 1),
                'description'    => 'Sản phẩm chất lượng cao, bảo hành chính hãng 12 tháng. ' . $name . ' mang lại trải nghiệm tuyệt vời cho người dùng.',
                'price'          => $price,
                'original_price' => $originalPrice,
                'stock'          => rand(5, 100),
                'is_new'         => $index < 15,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}