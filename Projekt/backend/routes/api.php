<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\KitchenController;


// Publikus útvonalak
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Védett útvonalak
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // User profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'changePassword']);
    });
    
    // Menu (minden bejelentkezett felhasználó számára)
    Route::prefix('menu')->group(function () {
        Route::get('/today', [MenuController::class, 'getTodayMenu']);
        Route::get('/week', [MenuController::class, 'getWeeklyMenu']);
    });
    
    // Admin útvonalak
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']);

        Route::get('/users/{user}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    });
    
    // Konyha útvonalak
    Route::prefix('kitchen')->group(function () {
        Route::get('/meals', [KitchenController::class, 'getMeals']);
        Route::post('/meals', [KitchenController::class, 'createMeal']);
        Route::get('/orders/today', [KitchenController::class, 'getTodayOrders']);
    });

    /*Route::prefix('users')->group(function () {
        Route::get('/{id}', [UserController::class, 'show']);      // HIÁNYZIK!
        Route::put('/{id}', [UserController::class, 'update']);    // HIÁNYZIK!
    });*/
});