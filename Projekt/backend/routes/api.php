<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\MenuController;


// Publikus Ăştvonalak
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/reset-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password/confirm', [PasswordResetController::class, 'reset']);
// Opcionális tokenellenőrzés
Route::post('/reset-password/check-token', [PasswordResetController::class, 'checkToken']);


Route::get('/menu/available-dates', [MenuController::class, 'availableDates']);
Route::get('/menu/existing-dates', [MenuController::class, 'existingDates']);
Route::get('/menu/{date}', [MenuController::class, 'getMenuByDate']);
Route::post('/menu', [MenuController::class, 'saveMenu']);
Route::post('/menu', [MenuController::class, 'store']);



// védett utvonalak
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
    
    // Menu (minden bejelentkezettnek)
    Route::prefix('menu')->group(function () {
        Route::get('/today', [MenuController::class, 'getTodayMenu']);
        //Route::get('/week', [MenuController::class, 'getWeeklyMenu']);
    });
    
    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']);

        Route::get('/users/{user}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

        Route::get('/counties', [AdminController::class, 'getCounties']);
        Route::get('/cities/by-county/{county_id}', [AdminController::class, 'getCitiesByCounty']);
        Route::get('/cities/search', [AdminController::class, 'searchCities']);
    });
    
    // Konyha
    Route::prefix('kitchen')->group(function () {
        Route::get('/meals', [KitchenController::class, 'getMeals']);
        Route::post('/meals', [KitchenController::class, 'storeMeal']);
        Route::get('/meals/{id}', [KitchenController::class, 'showMeal']);
        Route::put('/meals/{id}', [KitchenController::class, 'updateMeal']);
        Route::delete('/meals/{id}', [KitchenController::class, 'deleteMeal']);
        
    
    // Kategóriák
    Route::get('/categories', [KitchenController::class, 'getCategories']);


        Route::get('/orders/today', [KitchenController::class, 'getTodayOrders']);
    });
});
Route::post('/reset-password', [App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLinkEmail']);

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post("/api/simple-password-reset", [App\Http\Controllers\VerySimplePasswordController::class, "sendReset"]);

Route::post("/test-password", function(Illuminate\Http\Request $request) {
    return response()->json([
        "status" => "OK",
        "message" => "Backend működik!",
        "received_email" => $request->input("email", "nincs"),
        "server_time" => date("Y-m-d H:i:s")
    ]);
});