<?php

namespace App\Http\Middleware;

use App\Services\SignatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 校验 License API 请求签名与时间戳。
 * 客户端需在请求体中携带 timestamp、nonce、sign。
 */
class VerifyLicenseSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $sig = SignatureService::make();
        $params = $request->all();

        if (! $sig->timestampValid($params['timestamp'] ?? null)) {
            return response()->json([
                'code'    => 4001,
                'message' => '请求已过期，请检查系统时间',
            ], 400);
        }

        if (! $sig->verifyRequest($params)) {
            return response()->json([
                'code'    => 4002,
                'message' => '签名校验失败',
            ], 401);
        }

        return $next($request);
    }
}
