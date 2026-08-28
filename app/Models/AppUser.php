<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'username', 'password', 'email', 'expires_at', 'is_banned',
        'machine_code', 'max_devices', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'expires_at'    => 'datetime',
        'last_login_at' => 'datetime',
        'is_banned'     => 'boolean',
    ];

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /** 账号是否处于有效期内 */
    public function isActive(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isFuture();
    }

    /** 剩余秒数（已过期返回 0） */
    public function remainingSeconds(): int
    {
        if (! $this->expires_at) {
            return 0;
        }
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    /**
     * 在当前有效期基础上追加天数（若已过期则从现在起算）。
     */
    public function extendDays(int $days): void
    {
        $base = ($this->expires_at && $this->expires_at->isFuture())
            ? $this->expires_at
            : now();
        $this->expires_at = $base->copy()->addDays($days);
        $this->save();
    }
}
