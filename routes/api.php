<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\QrCodeController;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Redirecionamento público por slug e QR público por slug
Route::get('/s/{slug}', [RedirectController::class, 'redirect']);
Route::get('/qrcode/{slug}', [QrCodeController::class, 'showBySlug']);

// Rotas protegidas: exigem Bearer token SEM sessão/cookie
Route::middleware(['auth.api_only', 'auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout', [AuthController::class, 'logout']); // opcional

    // Links
    Route::post('/links', [LinkController::class, 'store']);
    Route::get('/links', [LinkController::class, 'index']);
    Route::get('/links/{id}', [LinkController::class, 'show']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Métricas JSON
    Route::get('/metrics/summary', [MetricsController::class, 'summary']);
    Route::get('/metrics/top', [MetricsController::class, 'top']);
    Route::get('/metrics/by-month', [MetricsController::class, 'byMonth']);

    // QR por ID (somente dono)
    Route::get('/links/{link}/qrcode', [QrCodeController::class, 'showById']);
});