<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 餵食
Route::group([
    'prefix' => 'feed',
    'as' => 'Feed',
], function () {
    // 客戶投食貓咪
    Route::post('cat', [FeedController::class, 'feedToCat'])->middleware('auth.check');
    // IOT 設備設定值
    Route::post('iot/setting', [FeedController::class, 'iotSetting']);
    // IOT設備抓取是否有餵食紀錄
    Route::post('iot/{id}', [FeedController::class, 'iotGetData']);
    // IOT確認餵食刪除單筆資料
    Route::post('add/iot/{id}', [FeedController::class, 'addIot'])->middleware('auth.check');
});

