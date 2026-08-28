<?php

namespace App\Services;

/**
 * HMAC 签名工具。
 *
 * 设计目标：
 *  - 请求防篡改：客户端用共享密钥对参数排序后签名，服务器重算比对。
 *  - 防重放：请求携带 timestamp + nonce，服务器校验时间窗口。
 *  - 响应防伪：服务器对返回体签名，客户端校验，防止本地 hosts / 代理伪造返回。
 *
 * 注意：HMAC 密钥内嵌在客户端二进制里始终存在被逆向的风险，
 * 因此它是“提高破解成本”的手段，而非绝对安全。若要更强保护，
 * 可叠加：TLS 证书绑定(pinning)、白盒密钥、服务端风控。
 */
class SignatureService
{
    public function __construct(
        private string $requestSecret,
        private string $responseSecret,
        private int $tolerance,
    ) {}

    public static function make(): self
    {
        return new self(
            (string) config('license.hmac_secret'),
            (string) config('license.sign_secret'),
            (int) config('license.timestamp_tolerance', 300),
        );
    }

    /**
     * 生成规范化待签字符串：按 key 升序拼接 key=value，跳过 sign 字段。
     */
    public function canonical(array $params): string
    {
        unset($params['sign']);
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $pairs[] = $k . '=' . $v;
        }
        return implode('&', $pairs);
    }

    public function signRequest(array $params): string
    {
        return hash_hmac('sha256', $this->canonical($params), $this->requestSecret);
    }

    public function verifyRequest(array $params): bool
    {
        if (! isset($params['sign'])) {
            return false;
        }
        $expected = $this->signRequest($params);
        return hash_equals($expected, (string) $params['sign']);
    }

    /** 校验请求时间戳，防重放。 */
    public function timestampValid(mixed $timestamp): bool
    {
        if (! is_numeric($timestamp)) {
            return false;
        }
        return abs(time() - (int) $timestamp) <= $this->tolerance;
    }

    /** 对服务器响应数据签名，供客户端校验返回真伪。 */
    public function signResponse(array $data): string
    {
        return hash_hmac('sha256', $this->canonical($data), $this->responseSecret);
    }
}
