<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * 信任的代理。'*' 表示信任所有上游代理的 X-Forwarded-* 头。
     * 因为 Caddy 与应用在同一 Docker 网络内，外部无法直接伪造，
     * 使用 '*' 是安全且简便的。
     */
    protected $proxies = '*';

    /**
     * 需要识别的转发头：包含 Proto，确保反代下 Laravel 生成 https:// URL。
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
