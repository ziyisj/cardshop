<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        // 支付回调等外部无法携带 CSRF 的接口在此排除
        // 'pay/*/notify',
    ];
}
