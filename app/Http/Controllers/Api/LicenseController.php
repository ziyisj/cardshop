<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LicenseController extends Controller
{
    public function __construct(
        private LicenseService $license,
    ) {}

    /**
     * POST /api/v1/login
     * body: username, password, machine_code?, timestamp, nonce, sign
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'     => 'required|string|max:64',
            'password'     => 'required|string|max:128',
            'machine_code' => 'nullable|string|max:128',
        ]);

        try {
            $result = $this->license->authenticate(
                $data['username'],
                $data['password'],
                $data['machine_code'] ?? null,
                $request->ip(),
            );
        } catch (RuntimeException $e) {
            return $this->signed([
                'code'    => $e->getCode() ?: 1000,
                'message' => $e->getMessage(),
            ], 200);
        }

        $user = $result['user'];

        return $this->signed([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'username'          => $user->username,
                'expires_at'        => optional($user->expires_at)->timestamp,
                'expires_at_human'  => optional($user->expires_at)->toDateTimeString(),
                'remaining_seconds' => $user->remainingSeconds(),
                'server_time'       => time(),
            ],
        ], 200);
    }

    /**
     * POST /api/v1/heartbeat
     * 用于程序运行期间周期性复验有效期。
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'     => 'required|string|max:64',
            'machine_code' => 'nullable|string|max:128',
        ]);

        $user = \App\Models\AppUser::where('username', $data['username'])->first();

        if (! $user || $user->is_banned || ! $user->isActive()) {
            return $this->signed([
                'code'    => 1003,
                'message' => '账号无效或已过期',
            ]);
        }

        if (($data['machine_code'] ?? null) && $user->machine_code
            && $user->machine_code !== $data['machine_code']) {
            return $this->signed([
                'code'    => 1004,
                'message' => '设备不匹配',
            ]);
        }

        return $this->signed([
            'code' => 0,
            'data' => [
                'remaining_seconds' => $user->remainingSeconds(),
                'server_time'       => time(),
            ],
        ]);
    }

    /**
     * POST /api/v1/redeem
     * 卡密激活/续期。body: code, username, password?, register?
     */
    public function redeem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'     => 'required|string|max:64',
            'username' => 'required|string|max:64',
            'password' => 'nullable|string|max:128',
            'register' => 'nullable|boolean',
        ]);

        try {
            $user = $this->license->redeemCard(
                $data['code'],
                $data['username'],
                $data['password'] ?? null,
                (bool) ($data['register'] ?? false),
            );
        } catch (RuntimeException $e) {
            return $this->signed([
                'code'    => $e->getCode() ?: 2000,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->signed([
            'code'    => 0,
            'message' => '激活成功',
            'data'    => [
                'username'         => $user->username,
                'expires_at'       => optional($user->expires_at)->timestamp,
                'expires_at_human' => optional($user->expires_at)->toDateTimeString(),
            ],
        ]);
    }

    /**
     * 统一签名响应：附加 server response 的 HMAC，客户端可校验返回真伪。
     */
    private function signed(array $payload, int $status = 200): JsonResponse
    {
        $sig = SignatureService::make();
        // 仅对稳定的顶层标量字段签名，避免嵌套顺序问题
        $flat = [
            'code'        => $payload['code'] ?? -1,
            'server_time' => $payload['data']['server_time'] ?? time(),
            'nonce'       => bin2hex(random_bytes(8)),
        ];
        $payload['nonce'] = $flat['nonce'];
        $payload['sign']  = $sig->signResponse($flat);

        return response()->json($payload, $status);
    }
}
