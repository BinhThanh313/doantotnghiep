<?php

return [

    // ── VNPay ──────────────────────────────────────────────
    'vnpay' => [
        'tmn_code'    => env('VNPAY_TMN_CODE', 'DEMOVNPAY'),
        'hash_secret' => env('VNPAY_HASH_SECRET', 'SECRETKEY'),
        'url'         => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'refund_url'  => env('VNPAY_REFUND_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
        'return_url'  => env('VNPAY_RETURN_URL', '/api/payment/vnpay/callback'),
    ],

    // ── MoMo ───────────────────────────────────────────────
    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE', 'MOMOBKUN20180529'),
        'access_key'   => env('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j'),
        'secret_key'   => env('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoTkhwnSubUGbLt'),
        'endpoint'     => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'return_url'   => env('MOMO_RETURN_URL', '/api/payment/momo/callback'),
        'notify_url'   => env('MOMO_NOTIFY_URL', '/api/payment/momo/notify'),
    ],

    // ── Bank Transfer ──────────────────────────────────────
    'bank' => [
        'name'           => env('BANK_NAME', 'Vietcombank'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', '1234567890'),
        'account_name'   => env('BANK_ACCOUNT_NAME', 'CONG TY TNHH ELECTRO'),
        'branch'         => env('BANK_BRANCH', 'Chi nhánh Hà Nội'),
    ],

];