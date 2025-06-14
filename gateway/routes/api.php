<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

//Test route to check if the gateway is working
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
// Public routes
Route::post('/auth/login', [GatewayController::class, 'proxyToAuth']);
Route::post('/auth/register', [GatewayController::class, 'proxyToAuth']);

// Protected routes
Route::middleware('gateway.auth')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [GatewayController::class, 'proxyToAuth']);
    Route::post('/auth/refresh', [GatewayController::class, 'proxyToAuth']);
    // Route::get('/auth/me', [GatewayController::class, 'proxyToAuth']);
});
