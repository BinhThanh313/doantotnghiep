<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductSpecificationGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSpecificationSeeder extends Seeder
{
    public function run(): void
    {
        $generator = new ProductSpecificationGenerator();

        // Xóa specs cũ (nếu seed lại nhiều lần) để tránh trùng lặp dữ liệu
        DB::table('product_specifications')->truncate();

        Product::with('category')->chunk(20, function ($products) use ($generator) {
            foreach ($products as $product) {
                $rows = $generator->generate(
                    $product->category->name ?? '',
                    $product->name,
                    (float) $product->price
                );

                if (empty($rows)) {
                    continue;
                }

                $now = now();
                $insert = [];
                foreach ($rows as $order => $row) {
                    $insert[] = [
                        'product_id' => $product->id,
                        'group_name' => $row['group'],
                        'label'      => $row['label'],
                        'value'      => $row['value'],
                        'unit'       => $row['unit'],
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('product_specifications')->insert($insert);
            }
        });

        $this->command->info('Đã tạo thông số kỹ thuật cho ' . Product::count() . ' sản phẩm.');
    }
}