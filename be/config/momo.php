<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MoMo Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình kết nối MoMo Payment Gateway
    | Test environment: https://test-payment.momo.vn
    | Production: https://payment.momo.vn
    |
    */

    // Môi trường: 'test' hoặc 'production'
    'environment' => env('MOMO_ENVIRONMENT', 'test'),

    // Partner Code - Mã định danh merchant (lấy từ MoMo Developer Portal)
    'partner_code' => env('MOMO_PARTNER_CODE', 'MOMOIQA420180417'),

    // Access Key - Dùng để tạo signature
    'access_key' => env('MOMO_ACCESS_KEY', 'SvDmj2cOTYZmQQ3H'),

    // Secret Key - Dùng để mã hóa signature (KHÔNG BAO GIỜ expose ra client)
    'secret_key' => env('MOMO_SECRET_KEY', 'mNsDGHMnNWg2HmRGN7HGNHGGNmR2HmR2'),

    // API Endpoint
    'endpoint' => [
        'test' => 'https://test-payment.momo.vn',
        'production' => 'https://payment.momo.vn',
    ],

    // Callback URLs
    'redirect_url' => env('MOMO_REDIRECT_URL', 'http://localhost:5173/student/thanh-toan/ket-qua'),
    'ipn_url' => env('MOMO_IPN_URL', 'http://localhost:8000/api/momo/ipn'),

    // Request Type
    'request_type' => 'payWithATM',
];
