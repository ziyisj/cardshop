<?php

namespace Tests;

use App\Services\SignatureService;

trait SignsRequests
{
    /**
     * 给请求参数补上 timestamp/nonce/sign，返回可直接 POST 的完整数组。
     */
    protected function signed(array $params): array
    {
        $params['timestamp'] = $params['timestamp'] ?? time();
        $params['nonce']     = $params['nonce'] ?? bin2hex(random_bytes(8));
        $params['sign']      = SignatureService::make()->signRequest($params);

        return $params;
    }
}
