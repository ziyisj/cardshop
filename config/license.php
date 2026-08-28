<?php

return [
    // 请求签名共享密钥（客户端与服务器一致）
    'hmac_secret' => env('LICENSE_HMAC_SECRET', ''),

    // 响应签名密钥
    'sign_secret' => env('LICENSE_SIGN_SECRET', ''),

    // 时间戳容差（秒）
    'timestamp_tolerance' => (int) env('LICENSE_TIMESTAMP_TOLERANCE', 300),
];
