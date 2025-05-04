<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\StanController;
use App\Http\Controllers\API\SiswaController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\DiskonController;
use App\Http\Controllers\API\TransactionController;

// Public routes
Route::post('/register/siswa', [AuthController::class, 'registerSiswa']);
Route::post('/register/stan', [AuthController::class, 'registerStan']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', [AuthController::class, 'me']);
    
    // Stan routes
    Route::prefix('stan')->group(function () {
        Route::get('profile', [StanController::class, 'profile']);
        Route::put('profile', [StanController::class, 'updateProfile']);
        Route::get('income/{month?}/{year?}', [StanController::class, 'incomeByMonth']);
        
        // Admin Stan only routes
        Route::middleware('role:admin_stan')->group(function () {
            // Siswa management for stan admin
            Route::get('siswa', [StanController::class, 'getCustomers']);
            Route::get('siswa/{id}', [StanController::class, 'getCustomer']);
            Route::post('siswa', [StanController::class, 'createCustomer']);
            Route::put('siswa/{id}', [StanController::class, 'updateCustomer']);
            Route::delete('siswa/{id}', [StanController::class, 'deleteCustomer']);
            
            // Order status management
            Route::get('transaksi/{month?}/{year?}', [TransactionController::class, 'getStanTransaksiByMonth']);
            Route::put('transaksi/{id}/status', [TransactionController::class, 'updateStatus']);
        });
    });
    
    // Menu routes
    Route::prefix('menu')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::get('/{id}', [MenuController::class, 'show']);
        
        // Admin Stan only routes
        Route::middleware('role:admin_stan')->group(function () {
            Route::post('/', [MenuController::class, 'store']);
            Route::put('/{id}', [MenuController::class, 'update']);
            Route::delete('/{id}', [MenuController::class, 'destroy']);
        });
    });
    
    // Diskon routes
    Route::prefix('diskon')->group(function () {
        Route::get('/', [DiskonController::class, 'index']);
        Route::get('/active', [DiskonController::class, 'getActiveDiskon']);
        Route::get('/{id}', [DiskonController::class, 'show']);
        
        // Admin Stan only routes
        Route::middleware('role:admin_stan')->group(function () {
            Route::post('/', [DiskonController::class, 'store']);
            Route::put('/{id}', [DiskonController::class, 'update']);
            Route::delete('/{id}', [DiskonController::class, 'destroy']);
            
            // Menu diskon relations
            Route::post('/{diskonId}/menu/{menuId}', [DiskonController::class, 'attachMenu']);
            Route::delete('/{diskonId}/menu/{menuId}', [DiskonController::class, 'detachMenu']);
        });
    });
    
    // Siswa routes
    Route::prefix('siswa')->middleware('role:siswa')->group(function () {
        Route::get('profile', [SiswaController::class, 'profile']);
        Route::put('profile', [SiswaController::class, 'updateProfile']);
        
        // Transaksi routes for siswa
        Route::get('transaksi/{month?}/{year?}', [TransactionController::class, 'getSiswaTransaksiByMonth']);
        Route::get('transaksi/{id}', [TransactionController::class, 'show']);
        Route::post('transaksi', [TransactionController::class, 'store']);
        Route::get('transaksi/{id}/status', [TransactionController::class, 'checkStatus']);
        Route::get('transaksi/{id}/print', [TransactionController::class, 'printNota']);
    });
});
