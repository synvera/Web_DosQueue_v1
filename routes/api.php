<?php

use App\Http\Controllers\Api\Guest\TicketController;
use App\Http\Controllers\Api\Guest\WaveController;
use App\Http\Controllers\Api\Staff\AuthController;
use App\Http\Controllers\Api\Staff\QueueController;
use Illuminate\Support\Facades\Route;

// Public API (Guest)
Route::get('/waves', [WaveController::class, 'index']);
Route::post('/tickets', [TicketController::class, 'store'])->middleware('throttle:ticket-creation');
Route::get('/tickets/active', [TicketController::class, 'active']);
Route::get('/tickets/{token}', [TicketController::class, 'show']);
Route::get('/tickets/{token}/monitor', [TicketController::class, 'monitor'])->middleware('throttle:queue-polling');

// Staff API
Route::prefix('staff')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:staff-login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Manajemen Antrian Petugas
        Route::get('/queues', [QueueController::class, 'index']);
        Route::post('/tickets/{ticket}/call', [QueueController::class, 'call']);
        Route::post('/tickets/{ticket}/complete', [QueueController::class, 'complete']);
        Route::get('/history', [QueueController::class, 'history']);
        Route::delete('/tickets/{ticket}', [QueueController::class, 'destroy']);
    });
});