<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\CardKey;
use App\Models\LoginLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LicenseService
{
    /**
     * 校验账号密码 + 有效期 + 机器码。
     *
     * @return array{user:AppUser} 成功
     * @throws RuntimeException 失败（message 为原因）
     */
    public function authenticate(string $username, string $password, ?string $machineCode, ?string $ip): array
    {
        $user = AppUser::where('username', $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->log(null, $username, $ip, $machineCode, false, 'invalid_credentials');
            throw new RuntimeException('账号或密码错误', 1001);
        }

        if ($user->is_banned) {
            $this->log($user->id, $username, $ip, $machineCode, false, 'banned');
            throw new RuntimeException('账号已被封禁', 1002);
        }

        if (! $user->isActive()) {
            $this->log($user->id, $username, $ip, $machineCode, false, 'expired');
            throw new RuntimeException('账号已过期或未激活', 1003);
        }

        // 机器码绑定校验
        if ($machineCode) {
            if ($user->machine_code === null) {
                // 首次绑定
                $user->machine_code = $machineCode;
            } elseif ($user->machine_code !== $machineCode) {
                $this->log($user->id, $username, $ip, $machineCode, false, 'machine_mismatch');
                throw new RuntimeException('该账号已绑定其他设备', 1004);
            }
        }

        $user->last_login_at = now();
        $user->last_login_ip = $ip;
        $user->save();

        $this->log($user->id, $username, $ip, $machineCode, true, null);

        return ['user' => $user];
    }

    /**
     * 用卡密激活/续期账号。
     * 若账号不存在可选择自动创建（register=true）。
     */
    public function redeemCard(string $code, string $username, ?string $password, bool $register = false): AppUser
    {
        return DB::transaction(function () use ($code, $username, $password, $register) {
            /** @var CardKey|null $card */
            $card = CardKey::where('code', $code)->lockForUpdate()->first();

            if (! $card) {
                throw new RuntimeException('卡密不存在', 2001);
            }
            if (! $card->isUsable()) {
                throw new RuntimeException('卡密已被使用或已失效', 2002);
            }

            $user = AppUser::where('username', $username)->first();

            if (! $user) {
                if (! $register) {
                    throw new RuntimeException('账号不存在', 2003);
                }
                if (! $password) {
                    throw new RuntimeException('注册需要设置密码', 2004);
                }
                $user = AppUser::create([
                    'username' => $username,
                    'password' => Hash::make($password),
                ]);
            }

            $user->extendDays($card->duration_days);

            $card->update([
                'status'  => 'used',
                'used_by' => $user->id,
                'used_at' => now(),
            ]);

            return $user->refresh();
        });
    }

    private function log(?int $userId, string $username, ?string $ip, ?string $mc, bool $ok, ?string $reason): void
    {
        LoginLog::create([
            'app_user_id'  => $userId,
            'username'     => $username,
            'ip'           => $ip,
            'machine_code' => $mc,
            'success'      => $ok,
            'reason'       => $reason,
        ]);
    }
}
