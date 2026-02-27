<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AddressController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/categories', [ProductController::class, 'categories']);
Route::get('/products/filters', [ProductController::class, 'filters']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

// LGPD
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/data-export', [PrivacyController::class, 'exportData']);
    Route::post('/me/forget', [PrivacyController::class, 'forgetMe']);
    Route::post('/me/consent', [PrivacyController::class, 'updateConsent']);
});
    Route::apiResource('addresses', AddressController::class);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);

// Carrinho Abandonado
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/abandoned-cart', [AbandonedCartController::class, 'store']);
    Route::get('/abandoned-cart/recover', [AbandonedCartController::class, 'recover']);
});
});



