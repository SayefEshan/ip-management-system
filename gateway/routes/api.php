<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

//Test route to check if the gateway is working
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Public routes (no authentication required)
Route::post('/auth/login', [GatewayController::class, 'proxyToAuth']);
Route::post('/auth/refresh', [GatewayController::class, 'proxyToAuth']);

// Protected routes (authentication required)
Route::middleware('gateway.auth')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [GatewayController::class, 'proxyToAuth']);

    // IP Management routes
    Route::get('/ip-addresses', [GatewayController::class, 'proxyToApp']);
    Route::post('/ip-addresses', [GatewayController::class, 'proxyToApp']);
    Route::put('/ip-addresses/{id}', [GatewayController::class, 'proxyToApp']);
    Route::delete('/ip-addresses/{id}', [GatewayController::class, 'proxyToApp']);

    // Audit log routes
    Route::get('/audit-logs/session', [GatewayController::class, 'proxyToApp']);
    Route::get('/audit-logs/user', [GatewayController::class, 'proxyToApp']);
    Route::get('/audit-logs/ip-address/{ip}/session', [GatewayController::class, 'proxyToApp']);
    Route::get('/audit-logs/ip-address/{ip}', [GatewayController::class, 'proxyToApp']);
    Route::get('/audit-logs/all', [GatewayController::class, 'proxyToApp']);
});

// Internal route for gateway validation (not exposed to frontend)
Route::get('/auth/validate', [GatewayController::class, 'proxyToAuth']);