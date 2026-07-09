<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        // Thời gian tồn tại dài (bắt đầu từ hôm qua, kết thúc sau 1 năm)
        // để chương trình luôn ở trạng thái "đang chạy" khi demo / bảo vệ đồ án.
        $start = Carbon::now()->subDay();
        $end   = Carbon::now()->addYear();

        $flashSale = FlashSale::updateOrCreate(
            ['name' => 'Flash Sale Cực Sốc'],
            [
                'description' => 'Chương trình giảm giá sốc cho các sản phẩm công nghệ hot nhất tại Electro Shop.',
                'starts_at'   => $start,
                'ends_at'     => $end,
                'is_active'   => true,
            ]
        );

        $products = Product::where('is_active', true)->inRandomOrder()->limit(8)->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Chưa có sản phẩm nào trong DB, hãy chạy ProductSeeder trước FlashSaleSeeder.');
            return;
        }

        foreach ($products as $product) {
            // Giảm ngẫu nhiên 15% - 45% so với giá gốc, làm tròn tới hàng nghìn
            $discountPercent = rand(15, 45) / 100;
            $salePrice = (int) round($product->price * (1 - $discountPercent), -3);
            $salePrice = max($salePrice, 1000);

            FlashSaleItem::updateOrCreate(
                [
                    'flash_sale_id' => $flashSale->id,
                    'product_id'    => $product->id,
                ],
                [
                    'sale_price' => $salePrice,
                    'qty_limit'  => rand(20, 100),
                    'qty_sold'   => rand(0, 15),
                    'is_active'  => true,
                ]
            );
        }

        $this->command?->info('Đã tạo Flash Sale "'.$flashSale->name.'" với '.$products->count().' sản phẩm, chạy tới ' . $end->format('d/m/Y') . '.');
    }
}