<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';

    public function boot(): void
    {
        // 通用 API 限流：按 IP，120 次/分钟
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // 登录接口专用：更严格，按 账号+IP 组合，防撞库/暴力破解
        // 同一 (账号,IP) 每分钟最多 10 次，且整个 IP 每分钟不超过 30 次
        RateLimiter::for('license-login', function (Request $request) {
            $username = (string) $request->input('username');
            return [
                Limit::perMinute(10)->by($username . '|' . $request->ip()),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        // 卡密兑换接口：按 IP 限流，防止批量试卡
        RateLimiter::for('license-redeem', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
