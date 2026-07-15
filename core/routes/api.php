<?php

use App\Http\Controllers\Internal\InternalCallbackController;
use App\Http\Controllers\Webhook\DigiflazzWebhookController;
use App\Http\Middleware\InternalIpAllowlist;
use App\Http\Middleware\VerifyInternalToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('internal')->middleware([InternalIpAllowlist::class, VerifyInternalToken::class])->group(function () {
    Route::post('/callback', [InternalCallbackController::class, 'handle']);
});

Route::post('/webhooks/digiflazz', [DigiflazzWebhookController::class, 'handle']);
