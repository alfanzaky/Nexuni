<?php

use App\Http\Controllers\Internal\InternalCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('internal')->group(function () {
    Route::post('/callback', [InternalCallbackController::class, 'handle']);
});
