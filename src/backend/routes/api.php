<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public: health & metrics
Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/metrics', \Spatie\Prometheus\Http\Controllers\PrometheusMetricsController::class)
    ->name('prometheus.default');

// Auth (public) with rate limiting
Route::middleware('throttle:auth')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Auth (protected)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{productId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{productId}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/checkout', [OrderController::class, 'checkout']);
});

// Public: read products & categories, serve images
Route::get('/products/{product}/image', [ProductController::class, 'showImage']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);

// Admin only
Route::middleware(['auth:sanctum', 'admin', 'throttle:api'])->group(function () {
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    Route::post('/products/{product}/image', [ProductController::class, 'uploadImage']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});
