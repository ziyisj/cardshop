<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** 每个 邮箱+IP 组合允许的最大失败次数 */
    private const MAX_ATTEMPTS = 5;

    /** 触发锁定后的封锁时长（秒） */
    private const DECAY_SECONDS = 300;

    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $key = $this->throttleKey($request);

        // 已达上限则拒绝并提示剩余等待时间
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "尝试次数过多，请在 {$seconds} 秒后重试。",
            ]);
        }

        if (Auth::guard('admin')->attempt($data, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        // 失败计数 +1（DECAY_SECONDS 后自动衰减）
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $remaining = self::MAX_ATTEMPTS - RateLimiter::attempts($key);
        $msg = '邮箱或密码错误';
        if ($remaining >= 0 && $remaining <= 2) {
            $msg .= "（还可尝试 {$remaining} 次）";
        }

        return back()->withErrors(['email' => $msg])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    /** 限流键：邮箱（小写）+ 客户端 IP */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower((string) $request->input('email')) . '|' . $request->ip()
        );
    }
}
