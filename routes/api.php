<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\QrCodeController; 

// Rotas públicas ------------------------------

// QR Code público por slug 
Route::get('/qrcode/{slug}', [QrCodeController::class, 'showBySlug']);

// Teste rápido (opcional)
Route::get('/ping', function () {
    return response()->json(['message' => 'API Online']);
});

// Registro e login
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Redirecionamento pelo slug
Route::get('/s/{slug}', [\App\Http\Controllers\RedirectController::class, 'bySlug']);

// Rotas protegidas (somente autenticados) -----
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Links
    Route::post('/links', [LinkController::class, 'store']);      // criar link
    Route::get('/links', [LinkController::class, 'index']);       // listar links do usuário
    Route::get('/links/{id}', [LinkController::class, 'show']);   // detalhes de um link

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Métricas JSON
    Route::get('/metrics/summary', [MetricsController::class, 'summary']);
    Route::get('/metrics/top', [MetricsController::class, 'top']);
    Route::get('/metrics/by-month', [MetricsController::class, 'byMonth']);

    // QR Code por ID – apenas o dono do link
    Route::get('/links/{link}/qrcode', [QrCodeController::class, 'showById']);
});