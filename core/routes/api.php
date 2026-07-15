<?php

use App\Http\Controllers\Internal\InternalCallbackController;
use App\Http\Middleware\InternalIpAllowlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('internal')->middleware([InternalIpAllowlist::class])->group(function () {
    Route::post('/callback', [InternalCallbackController::class, 'handle']);
});
