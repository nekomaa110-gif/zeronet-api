<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MikrotikController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // RADIUS Users
    Route::prefix('users')->name('api.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::delete('/{username}', [UserController::class, 'destroy'])
            ->name('destroy')
            ->where('username', '[a-zA-Z0-9._-]+');
    });

    // RADIUS Sessions
    Route::get('/online', [SessionController::class, 'online'])->name('api.sessions.online');

    // Dashboard Stats
    Route::get('/stats', [UserController::class, 'stats'])->name('api.stats');

    // Mikrotik
    Route::prefix('mikrotik')->name('api.mikrotik.')->group(function () {
        Route::get('/active', [MikrotikController::class, 'activeUsers'])->name('active');
        Route::post('/disconnect', [MikrotikController::class, 'disconnect'])->name('disconnect');
        Route::get('/ping', [MikrotikController::class, 'ping'])->name('ping');
    });
});
