<?php

return [

    // ── Bank Transfer ──────────────────────────────────────
    'bank' => [
        'name'           => env('BANK_NAME', 'Vietcombank'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', '1234567890'),
        'account_name'   => env('BANK_ACCOUNT_NAME', 'CONG TY TNHH ELECTRO'),
        'branch'         => env('BANK_BRANCH', 'Chi nhánh Hà Nội'),
        'vietqr_bank_id'    => env('BANK_VIETQR_ID', 'VCB'),
    ],

];