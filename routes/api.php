<?php

use App\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

/*
| License 校验 API。
| 所有接口均需通过 license.sign 中间件（签名 + 时间戳校验）。
*/
Route::prefix('v1')->middleware('license.sign')->group(function () {
    // 登录：按 账号+IP 严格限流，防撞库/暴力破解
    Route::post('login', [LicenseController::class, 'login'])
        ->middleware('throttle:license-login');

    Route::post('heartbeat', [LicenseController::class, 'heartbeat']);

    // 兑换：按 IP 限流，防批量试卡
    Route::post('redeem', [LicenseController::class, 'redeem'])
        ->middleware('throttle:license-redeem');
});
