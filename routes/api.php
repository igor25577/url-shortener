<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\QrCodeController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    // opcional manter este alias também
    Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth.api_only', 'auth:sanctum']);
});

// Alternativa pública para compatibilidade
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth.api_only', 'auth:sanctum']);

// Rotas públicas
Route::get('/s/{slug}', [RedirectController::class, 'redirect']);
Route::get('/qrcode/{slug}', [QrCodeController::class, 'showBySlug']);

// Rotas protegidas por Bearer + Sanctum
Route::middleware(['auth.api_only', 'auth:sanctum'])->group(function () {
    // Rate limit aplicado somente à criação: 30 requisições/min por usuário
    Route::post('/links', [LinkController::class, 'store'])->middleware('throttle:30,1');

    Route::get('/links', [LinkController::class, 'index']);
    Route::get('/links/{id}', [LinkController::class, 'show']);

    // QR Code por ID (apenas dono)
    Route::get('/links/{link}/qrcode', [QrCodeController::class, 'showById']);

    // Dashboard + métricas
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('metrics')->group(function () {
        Route::get('/summary', [MetricsController::class, 'summary']);
        Route::get('/top', [MetricsController::class, 'top']);
        Route::get('/by-month', [MetricsController::class, 'byMonth']);
    });
});