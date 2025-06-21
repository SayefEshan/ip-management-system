<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\IPAddressController;

// IP Address routes
Route::prefix('ip-addresses')->group(function () {
    Route::get('/', [IPAddressController::class, 'index']);
    Route::post('/', [IPAddressController::class, 'store']);
    Route::put('/{id}', [IPAddressController::class, 'update']);
    Route::delete('/{id}', [IPAddressController::class, 'destroy']);
});

// Audit log routes
Route::prefix('audit-logs')->group(function () {
    Route::get('/session', [AuditLogController::class, 'sessionLogs']);
    Route::get('/user', [AuditLogController::class, 'userLogs']);
    Route::get('/ip-address/{id}/session', [AuditLogController::class, 'ipSessionLogs']);
    Route::get('/ip-address/{id}', [AuditLogController::class, 'ipLogs']);
    Route::get('/all', [AuditLogController::class, 'allLogs']);
});
