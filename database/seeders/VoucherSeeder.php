<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        // Thời gian hiệu lực dài (bắt đầu từ 3 tháng trước, kết thúc sau 2 năm)
        // để voucher không bị hết hạn trong lúc demo / bảo vệ đồ án.
        $start = Carbon::now()->subMonths(3);
        $end   = Carbon::now()->addYears(2);

        $vouchers = [
            [
                'code'               => 'WELCOME10',
                'name'               => 'Chào mừng khách hàng mới - Giảm 10%',
                'discount_type'      => 'percent',
                'discount_value'     => 10,
                'min_amount'         => 500000,
                'max_discount'       => 200000,
                'max_uses'           => null,
                'max_uses_per_user'  => 1,
            ],
            [
                'code'               => 'SALE50K',
                'name'               => 'Giảm ngay 50.000đ cho đơn từ 300k',
                'discount_type'      => 'fixed',
                'discount_value'     => 50000,
                'min_amount'         => 300000,
                'max_discount'       => null,
                'max_uses'           => 500,
                'max_uses_per_user'  => 3,
            ],
            [
                'code'               => 'SUMMER20',
                'name'               => 'Ưu đãi mùa hè - Giảm 20%',
                'discount_type'      => 'percent',
                'discount_value'     => 20,
                'min_amount'         => 1000000,
                'max_discount'       => 500000,
                'max_uses'           => 200,
                'max_uses_per_user'  => 2,
            ],
            [
                'code'               => 'VIP100K',
                'name'               => 'Ưu đãi khách VIP - Giảm 100.000đ',
                'discount_type'      => 'fixed',
                'discount_value'     => 100000,
                'min_amount'         => 2000000,
                'max_discount'       => null,
                'max_uses'           => 100,
                'max_uses_per_user'  => 1,
            ],
            [
                'code'               => 'FREESHIP',
                'name'               => 'Giảm 30.000đ - Hỗ trợ phí ship',
                'discount_type'      => 'fixed',
                'discount_value'     => 30000,
                'min_amount'         => 0,
                'max_discount'       => null,
                'max_uses'           => null,
                'max_uses_per_user'  => 5,
            ],
        ];

        foreach ($vouchers as $voucher) {
            DB::table('vouchers')->updateOrInsert(
                ['code' => $voucher['code']],
                array_merge($voucher, [
                    'used_count'             => 0,
                    'start_date'             => $start,
                    'end_date'               => $end,
                    'applicable_categories'  => null,
                    'applicable_products'    => null,
                    'is_active'              => true,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ])
            );
        }

        $this->command?->info('Đã tạo/cập nhật ' . count($vouchers) . ' voucher, hiệu lực tới ' . $end->format('d/m/Y') . '.');
    }
}