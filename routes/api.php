<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LabRequestController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Accessible by User and Aslab
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/history', [LoanController::class, 'history']);
    Route::get('/rooms/{id}/available-desks', [RoomController::class, 'availableDesks']);

    // User only
    Route::middleware('role:user')->group(function () {
        Route::post('/loans', [LoanController::class, 'store']);
        Route::post('/lab-requests', [LabRequestController::class, 'store']);

        Route::post('/loans/check-in', [LoanController::class, 'checkIn']);
        Route::post('/loans/check-out', [LoanController::class, 'checkOut']);
    });

    // User & Aslab
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/lab-requests', [LabRequestController::class, 'index']);

    // Desk QR
    Route::get('/desks/{id}/qr', [LoanController::class, 'deskQr']);

    Route::get('/rooms', [RoomController::class, 'index']);
    
    //for desk management
    Route::get('/rooms/{id}/desks', [RoomController::class, 'indexDesks']); 
    Route::get('/rooms/{id}/available-desks', [RoomController::class, 'availableDesks']);

    // Aslab (Admin) only
    Route::middleware('role:aslab')->group(function () {
        Route::patch('/loans/{id}/approve', [LoanController::class, 'approve']);
        Route::patch('/loans/{id}/reject', [LoanController::class, 'reject']);

        Route::patch('/lab-requests/{id}/approve', [LabRequestController::class, 'approve']);
        Route::patch('/lab-requests/{id}/reject', [LabRequestController::class, 'reject']);

        Route::post('/rooms', [RoomController::class, 'store']);
        Route::post('/rooms/{id}/desks', [RoomController::class, 'storeDesks']);
    });
});
