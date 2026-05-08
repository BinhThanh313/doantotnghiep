<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Điện thoại',
            'Laptop',
            'Máy tính bảng',
            'Đồng hồ thông minh',
            'Tai nghe',
            'Camera',
            'Phụ kiện',
            'Tivi',
            'Máy ảnh',
            'Loa bluetooth',
        ];

        foreach ($categories as $name) {
            DB::table('categories')->insert([
                'name'       => $name,
                'slug'       => Str::slug($name) . '-' . Str::random(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}