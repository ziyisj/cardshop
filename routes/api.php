<?php

use App\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

/*
| License 校验 API。
| 所有接口均需通过 license.sign 中间件（签名 + 时间戳校验）。
*/
Route::prefix('v1')->middleware('license.sign')->group(function () {
    Route::post('login', [LicenseController::class, 'login']);
    Route::post('heartbeat', [LicenseController::class, 'heartbeat']);
    Route::post('redeem', [LicenseController::class, 'redeem']);
});
