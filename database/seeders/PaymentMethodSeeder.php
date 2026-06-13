<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name'        => 'Thanh toán khi nhận hàng',
                'code'        => 'cod',
                'is_active'   => true,
                'fee_percent' => 0,
                'config'      => null,
                'description' => 'Khách hàng thanh toán bằng tiền mặt khi shipper giao hàng.',
                'icon'        => 'fas fa-money-bill-wave',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Chuyển khoản ngân hàng',
                'code'        => 'bank',
                'is_active'   => true,
                'fee_percent' => 0,
                'config'      => json_encode([
                    'bank_name'      => env('BANK_NAME', 'Vietcombank'),
                    'account_number' => env('BANK_ACCOUNT_NUMBER', '1234567890'),
                    'account_name'   => env('BANK_ACCOUNT_NAME', 'CONG TY TNHH ELECTRO'),
                    'branch'         => env('BANK_BRANCH', 'Chi nhánh Hà Nội'),
                ]),
                'description' => 'Chuyển khoản trực tiếp vào tài khoản ngân hàng của cửa hàng.',
                'icon'        => 'fas fa-university',
                'sort_order'  => 2,
            ],
        ];

        foreach ($methods as $method) {
            DB::table('payment_methods')->updateOrInsert(
                ['code' => $method['code']],
                array_merge($method, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Đã seed ' . count($methods) . ' phương thức thanh toán.');
    }
}