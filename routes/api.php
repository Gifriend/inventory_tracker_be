<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\LoanController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Accessible by User and Aslab
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/rooms/{id}/available-desks', [RoomController::class, 'availableDesks']);

    // User only
    Route::middleware('role:user')->group(function () {
        Route::post('/loans', [LoanController::class, 'store']);

        Route::post('/loans/check-in', [LoanController::class, 'checkIn']);
        Route::post('/loans/check-out', [LoanController::class, 'checkOut']);
    });

    // User & Aslab
    Route::get('/loans', [LoanController::class, 'index']);
    
    //
    Route::get('/rooms/{id}/desks', [RoomController::class, 'indexDesks']); 
    Route::get('/rooms/{id}/available-desks', [RoomController::class, 'availableDesks']);

    // Aslab (Admin) only
    Route::middleware('role:aslab')->group(function () {
        Route::patch('/loans/{id}/approve', [LoanController::class, 'approve']);
        Route::patch('/loans/{id}/reject', [LoanController::class, 'reject']);

        Route::post('/rooms', [RoomController::class, 'store']);
        Route::post('/rooms/{id}/desks', [RoomController::class, 'storeDesks']);
    });
});
