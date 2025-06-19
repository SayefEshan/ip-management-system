<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

// Protected routes (token required but validated in controller)
Route::post('/auth/logout', [AuthController::class, 'logout']);

// Internal route for gateway validation
Route::get('/auth/validate', [AuthController::class, 'validateToken']);
