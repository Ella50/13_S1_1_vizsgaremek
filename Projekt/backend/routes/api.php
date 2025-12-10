<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\PasswordResetController;


// Publikus Ăştvonalak
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/reset-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password/confirm', [PasswordResetController::class, 'reset']);
// OpcionĂˇlis: token ellenĹ‘rzĂ©s
Route::post('/reset-password/check-token', [PasswordResetController::class, 'checkToken']);


// VĂ©dett Ăştvonalak
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
    
    // Menu (minden bejelentkezett felhasznĂˇlĂł szĂˇmĂˇra)
    Route::prefix('menu')->group(function () {
        Route::get('/today', [MenuController::class, 'getTodayMenu']);
        Route::get('/week', [MenuController::class, 'getWeeklyMenu']);
    });
    
    // Admin Ăştvonalak
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']);

        Route::get('/users/{user}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    });
    
    // Konyha Ăştvonalak
    Route::prefix('kitchen')->group(function () {
        Route::get('/meals', [KitchenController::class, 'getMeals']);
        Route::post('/meals', [KitchenController::class, 'createMeal']);
        Route::get('/orders/today', [KitchenController::class, 'getTodayOrders']);
    });
});
Route::post('/reset-password', [App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLinkEmail']);

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

// Very simple password reset
Route::post("/api/simple-password-reset", [App\Http\Controllers\VerySimplePasswordController::class, "sendReset"]);


// SUPER SIMPLE TEST ENDPOINT
Route::post("/test-password", function(Illuminate\Http\Request $request) {
    return response()->json([
        "status" => "OK",
        "message" => "Backend működik!",
        "received_email" => $request->input("email", "nincs"),
        "server_time" => date("Y-m-d H:i:s")
    ]);
});