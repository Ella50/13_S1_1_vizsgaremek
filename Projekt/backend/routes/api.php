<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;

// Nyilvános route-ok
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Városok lekérése (regisztrációhoz szükséges)
Route::get('/cities', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\City::all()
    ]);
});

// Védett route-ok (bejelentkezés szükséges)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User management
    Route::apiResource('users', UserController::class);
    Route::get('/users/type/{type}', [UserController::class, 'getByType']);
    Route::get('/users/status/{status}', [UserController::class, 'getByStatus']);
    Route::get('/users/class/{classId}', [UserController::class, 'getByClass']);

    // Egyéb resource-ok
    Route::get('/classes', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\StudentClass::all()
        ]);
    });

    Route::get('/groups', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\Group::all()
        ]);
    });

    Route::get('/rfidcards', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Models\RfidCard::all()
        ]);
    });
});