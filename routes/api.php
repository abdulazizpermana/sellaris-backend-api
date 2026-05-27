<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Products CRUD
    Route::apiResource('products', ProductController::class);
    Route::post('/create-product', [ProductController::class, 'store']);
    Route::get('/get-products',    [ProductController::class, 'index']);

    // AI Studio
    Route::post('/ai/generate',            [AIController::class, 'generate']);
    Route::match(['get', 'post'], '/ai/generate-content', [AIController::class, 'generate']);
    Route::post('/ai/generate-by-feature', [AIController::class, 'generateByFeature']);
    Route::post('/ai/generate-all', [AIController::class, 'generateAll']);
    Route::get('/ai/history/{product_id}', [AIController::class, 'history']);

    // Transactions
    Route::get('/transactions/history',      [TransactionController::class, 'history']);      // ← BARU
    Route::get('/transactions/daily-report', [TransactionController::class, 'dailyReport']);
    Route::get('/reports/daily',             [TransactionController::class, 'dailyReport']);
    Route::get('/reports/monthly',           [TransactionController::class, 'monthlyReport']);
    Route::post('/transactions',             [TransactionController::class, 'store']);
    Route::post('/create-transaction',       [TransactionController::class, 'store']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
