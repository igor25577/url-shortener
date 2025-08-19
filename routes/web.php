<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return response()->json([
        'error' => 'Login via web não disponível. Use a API em /api/auth/login.'
    ], 401);
})->name('login');
