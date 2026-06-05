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
            [
                'name'        => 'Ví MoMo',
                'code'        => 'momo',
                'is_active'   => true,
                'fee_percent' => 0,
                'config'      => json_encode([
                    'partner_code' => env('MOMO_PARTNER_CODE', 'MOMOBKUN20180529'),
                    'access_key'   => env('MOMO_ACCESS_KEY', ''),
                    'endpoint'     => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
                ]),
                'description' => 'Thanh toán nhanh qua ví điện tử MoMo.',
                'icon'        => 'fas fa-wallet',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'VNPay',
                'code'        => 'vnpay',
                'is_active'   => true,
                'fee_percent' => 0,
                'config'      => json_encode([
                    'tmn_code'    => env('VNPAY_TMN_CODE', 'DEMOVNPAY'),
                    'url'         => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
                    'return_url'  => env('VNPAY_RETURN_URL', '/api/payment/vnpay/callback'),
                ]),
                'description' => 'Thanh toán qua cổng VNPay — hỗ trợ ATM/Internet Banking/QR Code.',
                'icon'        => 'fas fa-credit-card',
                'sort_order'  => 4,
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